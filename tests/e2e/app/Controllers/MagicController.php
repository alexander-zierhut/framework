<?php

    /**
     * Experimental demo of <x-zubzet::island> / <x-zubzet::action>: a dashboard whose widgets
     * are ordinary actions rendering ordinary views. Nothing here knows it is loaded in place.
     * State lives in a JSON file so the demo needs no migration.
     */
    class MagicController extends z_controller {

        private static function store(?array $data = null): array {
            $file = sys_get_temp_dir() . "/zubzet_magic_demo.json";
            if($data !== null) file_put_contents($file, json_encode($data));
            if(!is_file($file)) return self::store([
                "name" => "Alex",
                "orders" => [
                    ["id" => 1, "customer" => "Acme GmbH", "total" => 1290, "shipped" => true],
                    ["id" => 2, "customer" => "Globex", "total" => 480, "shipped" => false],
                    ["id" => 3, "customer" => "Initech", "total" => 2150, "shipped" => false],
                ],
            ]);
            return json_decode(file_get_contents($file), true);
        }

        // The page: only island tags, no JS
        public function action_dashboard(Request $req, Response $res) {
            return $res->render("magic/dashboard", ["title" => "Magic dashboard"]);
        }

        // Header card, refreshes when the profile form saves
        public function action_greeting(Request $req, Response $res) {
            return $res->render("magic/greeting", ["name" => self::store()["name"]]);
        }

        // KPI tiles, polled. Unchanged numbers answer 304 and the DOM stays untouched.
        public function action_stats(Request $req, Response $res) {
            $orders = self::store()["orders"];
            return $res->render("magic/stats", [
                "count" => count($orders),
                "open" => count(array_filter($orders, fn($o) => !$o["shipped"])),
                "revenue" => array_sum(array_column($orders, "total")),
                "minute" => date("H:i"),
            ]);
        }

        // Orders table with action buttons and an add form (ZForm)
        public function action_orders(Request $req, Response $res) {
            if($req->hasFormData()) {
                $result = $req->validateForm([
                    (new FormField("customer"))->required()->length(2, 40),
                    (new FormField("total"))->required()->integer()->range(1, 100000),
                ]);
                if($result->hasErrors) return $res->formErrors($result->errors);

                $data = self::store();
                $data["orders"][] = [
                    "id" => max(array_column($data["orders"], "id") ?: [0]) + 1,
                    "customer" => $result->getValue("customer"),
                    "total" => (int) $result->getValue("total"),
                    "shipped" => false,
                ];
                self::store($data);
                $res->islandTrigger("orders.changed");
                return $res->success();
            }
            return $res->render("magic/orders", ["orders" => array_reverse(self::store()["orders"])]);
        }

        // Action button targets. Requiring the island header keeps cross-site POSTs out:
        // a browser cannot send a custom header cross-origin without a CORS preflight.
        public function action_ship(Request $req, Response $res) {
            if(!$req->isIsland()) return $res->error();
            $data = self::store();
            $id = (int) $req->getPost("id");
            foreach($data["orders"] as &$order) {
                if($order["id"] === $id) $order["shipped"] = !$order["shipped"];
            }
            self::store($data);
            $res->islandTrigger("orders.changed");
            $res->islandFlash("Order #$id updated");
            return $res->success();
        }

        public function action_delete(Request $req, Response $res) {
            if(!$req->isIsland()) return $res->error();
            $data = self::store();
            $id = (int) $req->getPost("id");
            $data["orders"] = array_values(array_filter($data["orders"], fn($o) => $o["id"] !== $id));
            self::store($data);
            $res->islandTrigger("orders.changed");
            $res->islandFlash("Order #$id deleted", "warning");
            return $res->success();
        }

        // Profile form: saving refreshes the greeting island elsewhere on the page
        public function action_profile(Request $req, Response $res) {
            if($req->hasFormData()) {
                $result = $req->validateForm([(new FormField("name"))->required()->length(2, 30)]);
                if($result->hasErrors) return $res->formErrors($result->errors);
                $data = self::store();
                $data["name"] = $result->getValue("name");
                self::store($data);
                $res->islandTrigger("profile.saved");
                return $res->success();
            }
            return $res->render("magic/profile", ["name" => self::store()["name"]]);
        }

        // A full page with a @fragment; islands can pull just the fragment
        public function action_report(Request $req, Response $res) {
            usleep(600000); // pretend this is expensive, it is loaded lazily
            $orders = self::store()["orders"];
            return $res->render("magic/report", ["orders" => $orders, "max" => max(array_column($orders, "total") ?: [1])]);
        }

        // Protected widget: anonymous island requests get a 401, never the login page
        public function action_secret(Request $req, Response $res) {
            $req->checkPermission("admin.panel");
            return $res->render("magic/secret");
        }

        // Page with a paginated list island
        public function action_list(Request $req, Response $res) {
            return $res->render("magic/list", ["title" => "Paginated list"]);
        }

        // The list itself: a plain GET action with page, sort and q parameters
        public function action_orderlist(Request $req, Response $res) {
            $customers = ["Acme", "Globex", "Initech", "Umbrella", "Hooli", "Stark", "Wayne", "Wonka", "Cyberdyne", "Tyrell"];
            $rows = [];
            for($i = 1; $i <= 57; $i++) {
                $rows[] = ["id" => $i, "customer" => $customers[($i * 7) % 10] . " #" . $i, "total" => ($i * 7919) % 5000 + 100];
            }

            $q = trim((string) $req->getGet("q", ""));
            if($q !== "") $rows = array_values(array_filter($rows, fn($r) => stripos($r["customer"], $q) !== false));

            $sort = in_array($req->getGet("sort"), ["id", "customer", "total"], true) ? $req->getGet("sort") : "id";
            $dir = $req->getGet("dir") === "desc" ? "desc" : "asc";
            usort($rows, fn($a, $b) => $dir === "asc" ? $a[$sort] <=> $b[$sort] : $b[$sort] <=> $a[$sort]);

            $perPage = 8;
            $pages = max(1, (int) ceil(count($rows) / $perPage));
            $page = min($pages, max(1, (int) $req->getGet("page", 1)));

            return $res->render("magic/orderlist", [
                "rows" => array_slice($rows, ($page - 1) * $perPage, $perPage),
                "page" => $page, "pages" => $pages, "sort" => $sort, "dir" => $dir, "q" => $q, "total" => count($rows),
            ]);
        }

        // Script patterns that break naive re-execution, to prove scopes handle them
        public function action_lab(Request $req, Response $res) {
            return $res->render("magic/lab", ["title" => "Scope lab"]);
        }

        // Reset the demo state
        public function action_reset(Request $req, Response $res) {
            @unlink(sys_get_temp_dir() . "/zubzet_magic_demo.json");
            return $res->rerouteUrl("magic/dashboard");
        }
    }

?>
