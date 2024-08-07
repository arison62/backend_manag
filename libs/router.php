
<?php
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, GET, DELETE, PUT, PATCH, OPTIONS');
    header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");


    header('Access-Control-Max-Age: 1728000');
    header('Content-Length: 0');
    header('Content-Type: application/json');
    die();
}

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header("Access-Control-Allow-Methods: POST, GET, OPTIONS, DELETE, PUT");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");


require 'http_utils.php';



class Router
{
    private $route = [];
    private $configs = [];

    public function __construct($configs)
    {

        $this->configs = $configs;
    }

    public function get($end_point, $handler)
    {
        $this->addRoute('get', $end_point, $handler);
    }

    public function post($end_point, $handler)
    {

        $this->addRoute('post', $end_point, $handler);
    }

    public function put($end_point, $handler)
    {

        $this->addRoute('put', $end_point, $handler);
    }

    public function delete($end_point, $handler)
    {

        $this->addRoute('delete', $end_point, $handler);
    }

    public function use($end_point, $handler)
    {
        $this->addRoute('use', $end_point, $handler);
    }


    private function addRoute($method, $end_point, $handler)
    {
        if (isset($this->configs['base_url']) && !empty($this->configs['base_url'])) {
            $end_point = $this->configs['base_url'] . $end_point;
        }

        array_push($this->route, array("method" => $method, "end_point" => $end_point, "handler" => $handler));
    }

    public function dispatch()
    {

        $uri_with_query = $_SERVER['REQUEST_URI'];
        $method = strtolower($_SERVER['REQUEST_METHOD']);
        $uri = parse_url($uri_with_query, PHP_URL_PATH);
        $route_found = false;
        $req = 'Request';
        $res = 'Response';
        
        foreach ($this->route as $route) {

            if ($route['method'] === $method || $route['method'] === 'use') {

                if ($route['end_point'] === '*' || $this->match($route['end_point'], $uri)) {

                    $route_found = true;
                    $query = [];
                    $params = [];


                    if (count($this->get_query($uri_with_query)) > 0) {
                        $query = $this->get_query($uri_with_query);
                    }
                    $params_length = 0;
                    $params_length = preg_match_all('/:\w+/', $route['end_point'], $params_name);
                    $params_name = preg_replace('/:/', '', $params_name[0]);

                    if ($params_length > 0) {
                        $params = $this->get_params($uri, $route['end_point'], $params_name);
                    }


                    $req::$params = $params;
                    $req::$query = $query;

                    if (is_array($route['handler'])) {

                        foreach ($route['handler'] as $handler) {
                            $handler($req, $res);
                        }
                    } else {
                        $route['handler']($req, $res);
                    }
                }
            }
        }

        if (!$route_found) {
            $res::status(404);
            $res::json(array("error" => true, "message" => "Not Found"));
        } else if ($route_found && !$res::$response_sent) {

            $res::status(500);
            $res::json(array("error" => "true", "message" => "Internal server error"));
        }
    }
    private function match($end_point, $uri)
    {
        error_log($end_point);
        $end_point = preg_replace('/:(\w+)/', '\w+', $end_point);
        $end_point = '#^' . $end_point . '$#';



        if (preg_match($end_point, $uri, $matches)) {

            return true;
        }
    }
    private function get_params($uri, $uri_pattern, $params_name)
    {

        $array_params = [];
        $params = [];
        $param_idx = 0;
        $i = 0;
        while ($i < strlen($uri)) {


            if (strcmp($uri_pattern[$i], ':') == 0) {

                $param_length = $this->count_word(substr($uri, $i, strlen($uri)));
                $param = substr($uri, $i, $param_length);

                array_push($params, $param);
                $array_params[$params_name[$param_idx]] = $param;
                $uri_pattern = substr($uri_pattern, 0, $i) . $param . substr($uri_pattern, $i + 1 + strlen($params_name[$param_idx]));
                $param_idx += 1;
            }

            $i += 1;
        }


        return $array_params;
    }

    private function get_query($uri)
    {
        $query = [];

        $query_pattern1 = '/\&([a-zA-Z0-9_])+=(\w)+/';
        $query_pattern2 = '/\?([a-zA-Z0-9_])+=(\w)+/';

        preg_match_all($query_pattern1, $uri, $matches, 2);

        foreach ($matches as $p) {
            $val = preg_filter('/\&/', '', $p[0]);
            $result = preg_split('/=/', $val);
            $query[$result[0]] = $result[1];
        }

        preg_match_all($query_pattern2, $uri, $matches, 2);

        foreach ($matches as $p) {
            $val = preg_replace('/\?/', '', $p[0]);
            $result = preg_split('/=/', $val);
            $query[$result[0]] = $result[1];
        }
        return $query;
    }

    private function count_word($word)
    {
        for ($x = 0; $x < strlen($word); $x++) {

            if (!ctype_alnum($word[$x])) {
                return $x;
            }
        }
    }
}



?>
    
