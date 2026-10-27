<?php

    namespace ZubZet\Framework\Rendering;

    use Blade\View;

    /**
     * Server side of <x-zubzet::island>.
     *
     * An island is a placeholder in a page that fetches any route of the application with the
     * Z-Island request header. For such requests the first top-level render drops its layout and
     * answers with the bare view (or one @fragment of it), an ETag, and a 304 when unchanged.
     * Requests without the header render exactly as before.
     *
     * Experimental.
     */
    class Island {

        private static bool $claimed = false;

        /**
         * Validates the attributes of an island component and returns what the markup needs.
         * Invalid values never reach the page: in tests they throw, otherwise the island is inert.
         */
        public static function describe(?string $src, ?string $fragment, $every, ?string $refreshOn): object {
            $error = self::invalidSrc($src)
                ?? self::invalidName($fragment, "fragment")
                ?? self::invalidEvents($refreshOn);

            if($error !== null) return self::inert($error);

            return (object) [
                "url" => rtrim(zubzet()->rootFolder, "/") . $src,
                "fragment" => $fragment ?? "",
                "everyMs" => self::interval($every),
                "refreshOn" => trim(preg_replace('/\s+/', " ", $refreshOn ?? "")),
            ];
        }

        /**
         * Claims island mode for the top-level render of an island request. Nested renders and
         * renders after the first (e.g. an email sent afterwards) stay normal.
         */
        public static function claim(): bool {
            if(self::$claimed) return false;
            if(!request()->isIsland()) return false;
            self::$claimed = true;
            return true;
        }

        public static function release(bool $claimed): void {
            if(!$claimed) return;
            self::$claimed = false;
        }

        /**
         * Sends the rendered view (or one fragment of it) as an island response.
         */
        public static function respond(View $view, ?string $fragment): void {
            $html = self::html($view, $fragment);
            if($html === null) self::refuse(404);

            $etag = 'W/"' . sha1($html) . '"';
            self::header("Z-Island", "1");
            self::header("Content-Type", "text/html; charset=utf-8");
            self::header("Cache-Control", "private, no-cache");
            self::header("ETag", $etag);

            if(self::etagMatches($etag)) {
                http_response_code(304);
                return;
            }

            echo $html;
        }

        /**
         * Ends an island request with an empty body and a status the client understands, instead
         * of rendering the login page, an error page or a redirect target into the island.
         * Does nothing for normal requests.
         */
        public static function refuse(int $status): void {
            if(!request()->isIsland()) return;
            http_response_code($status);
            self::header("Z-Island", "1");
            if($status === 401) self::header("Z-Island-Auth", "login");
            exit;
        }

        /**
         * Turns a redirect of an island request into an instruction for the client, as a fetch
         * would otherwise silently follow it and swap the target page into the island.
         */
        public static function redirect(string $location): void {
            if(!request()->isIsland()) return;
            http_response_code(204);
            self::header("Z-Island", "1");
            self::header("Z-Island-Redirect", $location);
            exit;
        }

        public static function header(string $name, string $value, bool $append = false): void {
            if(headers_sent()) return;
            if(!$append) {
                header("$name: $value");
                return;
            }
            header("$name: $value", false);
        }

        /** @return string[] Valid event names only */
        public static function eventNames(array $events): array {
            return array_values(array_filter($events, fn($e) => preg_match('/^[A-Za-z0-9_.:-]{1,64}$/', $e)));
        }

        private static function html(View $view, ?string $fragment): ?string {
            if($fragment === null) return (string) $view;
            // View::render() must return a string, so a missing fragment is reported by reference
            $found = false;
            $html = $view->render(function($engine) use ($fragment, &$found) {
                $found = isset($engine->fragments[$fragment]);
                return $engine->fragments[$fragment] ?? "";
            });
            if(!$found) return null;
            return $html;
        }

        private static function etagMatches(string $etag): bool {
            $sent = request()->header("If-None-Match");
            if($sent === null) return false;
            // Compression modules rewrite ETags to e.g. W/"abc-gzip"
            $normalize = fn($t) => preg_replace('/-(gzip|br|deflate)$/', "", trim(str_replace(['W/', '"'], "", $t)));
            return $normalize($sent) === $normalize($etag);
        }

        private static function interval($every): int {
            if($every === null || $every === "" || $every === false) return 0;
            if(!preg_match('/^(\d+)(ms|s|m)?$/', trim((string) $every), $m)) return 0;
            $factor = ["ms" => 1, "s" => 1000, "m" => 60000][$m[2] ?? "ms"] ?? 1;
            return max(2000, min(3600000, (int) $m[1] * $factor));
        }

        private static function invalidSrc(?string $src): ?string {
            if($src === null || $src === "") return "src is required";
            if(!preg_match('~^/(?!/)[^\\\\\s\x00-\x1f#]*$~', $src)) return "src must be an absolute path of this application, e.g. /stats/orders";
            $path = rawurldecode(parse_url($src, PHP_URL_PATH) ?? "");
            foreach(explode("/", $path) as $segment) {
                if($segment === "." || $segment === "..") return "src must not contain dot segments";
            }
            return null;
        }

        private static function invalidName(?string $name, string $what): ?string {
            if($name === null || $name === "") return null;
            if(preg_match('/^[A-Za-z0-9_-]{1,64}$/', $name)) return null;
            return "$what has an invalid name";
        }

        private static function invalidEvents(?string $events): ?string {
            if($events === null || trim($events) === "") return null;
            $names = preg_split('/\s+/', trim($events));
            if(count(self::eventNames($names)) === count($names)) return null;
            return "refresh-on has an invalid event name";
        }

        private static function inert(string $error): object {
            if(config("execution_type") === "test") throw new \InvalidArgumentException("<x-zubzet::island>: $error");
            trigger_error("<x-zubzet::island>: $error", E_USER_WARNING);
            return (object) ["url" => "", "fragment" => "", "everyMs" => 0, "refreshOn" => ""];
        }
    }

?>
