<?php
$root = $_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__);

require_once $root . '/models/db.php';

function get_user()
{
    return function ($req, $res) {
        global $root;
        $db_path = $root . '/' . $_ENV['DB_PATH'];
        $db = new MyDB($db_path);

        $user_id = $req::$headers['user_id'];
        if ($user_id == null) {
            $res::status(401);
            $res::json(array('error' => true, 'message' => 'Unauthorized', 'data' => []));
            return;
        }
        $user = $db->querySingle("SELECT * FROM Utilisateur WHERE id = '" . $user_id . "'", true);
        if ($user) {
            $res::status(200);
            unset($user['mot_de_passe']); // Supprimer le mot de passe  de l'utilisateur
            $res::json(array('error' => false, 'message' => 'User found', 'data' => $user));
            return;
        } else {
            $res::status(404);
            $res::json(array('error' => true, 'message' => 'User not found', 'data' => []));
            return;
        }
    };
}

function update_user()
{
    return function ($req, $res) {
        global $root;
        $db_path = $root . '/' . $_ENV['DB_PATH'];
        $db = new MyDB($db_path);
        $user_id = $req::$headers['user_id'];
        if ($user_id == null) {
            $res::status(401);
            $res::json(array('error' => true, 'message' => 'Unauthorized', 'data' => []));
            return;
        }
        $body = $req::body();
        $name = isset($body['name']) ? $body['name'] : null;
        $firstName = isset($body['firstName']) ? $body['firstName'] : null;
        $email = isset($body['email']) ? $body['email'] : null;
        $address = isset($body['address']) ? $body['address'] : null;
        $password = isset($body['password']) ? $body['password'] : null;
        $phone = isset($body['phone']) ? $body['phone'] : null;

       $password_hash = password_hash($password, PASSWORD_DEFAULT); 

        $query = "UPDATE Utilisateur SET ";
        $query .= empty($name) ? "" : "nom = '" . $name . "',";
        $query .= empty($email) ? "" : "email = '" . $email . "',";
        $query .= empty($address) ? "" : "adresse = '" . $address . "',";
        $query .= empty($password) ? "" : "mot_de_passe = '" . $password_hash . "',";
        $query .= empty($phone) ? "" : "telephone = '" . $phone . "',";
        $query .= empty($firstName) ? "": "prenom = '" .$firstName ."',";
        if($query[strlen($query) - 1] == ','){
            $query = substr($query, 0, -1);
        }
        $query .= " WHERE id = '" . $user_id . "'";
        $result = $db->exec($query);
        if ($result) {
            $res::status(200);
            $res::json(array('error' => false, 'message' => 'User updated', 'data' => []));
            return;
        } else {
            $res::status(500);
            $res::json(array('error' => true, 'message' => 'Internal server error', 'data' => []));
            return;
        }

    };
}


