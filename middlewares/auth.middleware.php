<?php

$root = $_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__);

require_once $root . '/vendor/autoload.php';

use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

function auth_middleware(){
    return function ($req, $res){

        if (!isset($_SERVER['HTTP_AUTHORIZATION']) || empty($_SERVER['HTTP_AUTHORIZATION'])) {
            $res::status(401);
            $res::json(array('error' => true, 'message' => 'Unauthorized', 'data' => []));
            return;
        }

        $auth = $_SERVER['HTTP_AUTHORIZATION'];

        if (!preg_match('/Bearer\s(\S+)/', $auth, $matches)) {
            $res::status(401);
            $res::json(array('error' => true, 'message' => 'Unauthorized', 'data' => []));
            return;
        }

        $jwt = $matches[1];
        $secret_key = $_ENV['SECRET_KEY'];
        try {
            $decoded = JWT::decode($jwt, new Key($secret_key, 'HS256'));
            $req::$headers['user_id'] = $decoded->user_id;
        } catch (ExpiredException $e) {
            $res::status(401);
            $res::json(array('error' => true, 'message' => 'Expired token', 'data' => []));
            return;
        
        } catch (Exception $e) {
            $res::status(401);
            $res::json(array('error' => true, 'message' => 'Unauthorizedi', 'data' => []));
            return;
        }
    };
}