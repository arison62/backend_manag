<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS, DELETE, PUT");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

class Response
{
    private static $status = 200;
    public static $response_sent = false;

    public static function status($code)
    {
        self::$status = $code;
    }
    public static function json($data)
    {
        if (self::$response_sent) {
            throw new Exception('Response already sent');
        }
        http_response_code(self::$status);
        header('Content-Type: application/json');
        echo json_encode($data);
        self::$response_sent = true;
    }

    public static function text($data)
    {
        if (self::$response_sent) {
            throw new Exception('Response already sent');
        }
        http_response_code(self::$status);
        header('Content-Type: text/plain');
        echo $data;
        self::$response_sent = true;
    }
}

class Request
{   public static $query = [];
    public static $params = [];
    public static $headers = [];

    public static function get($key)
    {
        return $_GET[$key];
    }
    public static function post($key)
    {
        return $_POST[$key];
    }

    public static function body()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if(!empty($_POST)) {
                return $_POST;
            }else{
                return json_decode(file_get_contents('php://input'), true);
            }
        } else {
            if(!empty($_GET)) {
                return $_GET;
            }else{
                return json_decode(file_get_contents('php://input'), true);
            }
        }
    }

    public static function files (){
        return $_FILES;
    }

    public static function header($key)
    {
        return $_SERVER[$key];
    }
}

?>
