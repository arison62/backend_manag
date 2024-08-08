<?php

$root = $_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__);

require_once $root . '/vendor/autoload.php';
require_once $root . '/models/db.php';

use Firebase\JWT\JWT;
use Dotenv\Dotenv;
use Firebase\JWT\ExpiredException;

$dotenv = Dotenv::createImmutable($root);
$dotenv->load();


function signup()
{

    return function ($req, $res) {
        global $root;
        $secret_key = $_ENV['SECRET_KEY'];

        $body = $req::body();
        $email = $body['email'];
        $password = $body['password'];
        $name = $body['name'];

        if (empty($email) || empty($password)) {
            $res::status(400);
            $res::json(array('error' => true, 'message' => 'Email and password are required', 'data' => []));
            return;
        } else {
            $db_path = $root . '/' . $_ENV['DB_PATH'];
            $db = new MyDB($db_path);
            $hash_password = password_hash($password, PASSWORD_DEFAULT);

            $user = $db->querySingle("SELECT * FROM Utilisateur WHERE email = '" . $email . "'");
            if ($user) {
                $res::status(409);
                $res::json(array('error' => true, 'message' => 'User already exists', 'data' => []));
                return;
            }
            $query = "INSERT INTO Utilisateur (nom, email, mot_de_passe) VALUES ('" . $name . "', '" . $email . "', '" . $hash_password . "')";
            $result = $db->exec($query);

            if ($result) {
                $user_id = $db->lastInsertRowID();
                $payload = array(
                    "email" => $email,
                    "user_id" => $user_id,
                    "exp" => time() + 3600 * 24 * 7 // token valide pendant 07jours
                );
                $jwt = JWT::encode($payload, $secret_key, 'HS256');

                $res::status(200);
                $res::json(array('error' => false, 'message' => 'User created', 'data' => array('token' => $jwt)));
                return;
            } else {
                $res::status(500);
                $res::json(array('error' => true, 'message' => 'User not created', 'data' => []));
                return;
            }
        }
    };
}

function login()
{
    return function ($req, $res) {
        global $root;
        $secret_key = $_ENV['SECRET_KEY'];

        $body = $req::body();
        $email = $body['email'];
        $password = $body['password'];

        if (empty($email) || empty($password)) {
            $res::status(400);
            $res::json(array('error' => true, 'message' => 'Email and password are required', 'data' => []));
            return;
        } else {
            $db_path = $root . '/' . $_ENV['DB_PATH'];
            $db = new MyDB($db_path);
            $user = $db->querySingle("SELECT * FROM Utilisateur WHERE email = '" . $email . "'", true);
            if ($user) {
                if (password_verify($password, $user['mot_de_passe'])) {
                    $payload = array(
                        "email" => $email,
                        "user_id" => $user['id'],
                        "exp" => time() + 3600 * 24 * 7 // token valide pendant 07jours
                    );

                    try {

                        $jwt = JWT::encode($payload, $secret_key, 'HS256');
                        $res::status(200);
                        $res::json(array('error' => false, 'message' => 'User logged in', 'data' => array('token' => $jwt)));
                        return ;
                    } catch ( ExpiredException $e) {
                        // token expire
                        $res::status(401);
                        $res::json(array('error' => true, 'message' => 'Token expired', 'data' => []));
                    } catch (Exception $e) {
                        // erreur serveur
                        $res::status(500);
                        $res::json(array('error' => true, 'message' => 'Internal server error', 'data' => []));
                    }

                    return;
                } else {

                    $res::status(401);
                    $res::json(array('error' => true, 'message' => 'Invalid credentials', 'data' => []));
                    return;
                }
            } else {
                $res::status(404);
                $res::json(array('error' => true, 'message' => 'User not found', 'data' => []));
                return;
            }
        }
    };
}
