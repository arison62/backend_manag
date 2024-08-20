<?php

$root = $_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__);

require_once $root . '/vendor/autoload.php';
require_once $root . '/models/db.php';

use Dotenv\Dotenv;


$dotenv = Dotenv::createImmutable($root);
$dotenv->load();

function add_customer()
{

    return function ($req, $res) {

        global $root;
        $user_id = $req::$headers['user_id'];
        if ($user_id == null) {
            $res::status(401);
            $res::json(array('error' => true, 'message' => 'Unauthorized', 'data' => []));
            return;
        }

        $db_path = $root . '/' . $_ENV['DB_PATH'];
        $db = new MyDB($db_path);

        $body = $req::body();

        if (empty($body['name'])) { // le client doit avoir au moins un nom
            $res::status(400);
            $res::json(array('error' => true, 'message' => 'Missing name', 'data' => []));
            return;
        } else {
            $nom = $body['name'];
            $email = isset($body['email']) ? $body['email'] : null;
            $telephone = isset($body['telephone']) ? $body['telephone'] : null;
            $address = isset($body['address']) ? $body['address'] : null;

            try {
                $query = 'INSERT INTO Client (nom, email, telephone, adresse, utilisateur_id) VALUES (:nom, :email, :telephone, :address, :utilisateur_id)';

                $stmt = $db->prepare($query);
                $stmt->bindValue(':nom', $nom);
                $stmt->bindValue(':email', $email);
                $stmt->bindValue(':telephone', $telephone);
                $stmt->bindValue(':address', $address);
                $stmt->bindValue(':utilisateur_id', $user_id);
                $result = $stmt->execute();

                if ($result) {
                    $res::status(200);
                    $res::json(array('error' => false, 'message' => 'Customer created', 'data' => []));
                    return;
                } else {
                    $res::status(500);
                    $res::json(array('error' => true, 'message' => 'Internal server error', 'data' => []));
                    return;
                }
            } catch (Throwable $e) {
                $res::status(500);
                $res::json(array('error' => true, 'message' => 'Internal server error', 'data' => []));
                return;
            }
        }
    };
}


function update_customer()
{
    return function ($req, $res) {

        global $root;
        $user_id = $req::$headers['user_id'];
        if ($user_id == null) {
            $res::status(401);
            $res::json(array('error' => true, 'message' => 'Unauthorized', 'data' => []));
            return;
        }

        $db_path = $root . '/' . $_ENV['DB_PATH'];
        $db = new MyDB($db_path);

        $body = $req::body();

        if (empty($body['id']) || empty($body['name'])) { // pour mettre a jour il faut au moins l'id
            $res::status(400);
            $res::json(array('error' => true, 'message' => 'Missing customer_id or name', 'data' => []));
            return;
        } else {
            $nom = $body['name'];
            $email = isset($body['email']) ? $body['email'] : null;
            $telephone = isset($body['telephone']) ? $body['telephone'] : null;
            $address = isset($body['address']) ? $body['address'] : null;
            $customer_id = $body['id'];

            try {
                $query = 'UPDATE Client SET nom = :nom, email = :email, telephone = :telephone, adresse = :address WHERE id = :id AND utilisateur_id = :utilisateur_id';
                $stmt = $db->prepare($query);
                $stmt->bindValue(':nom', $nom);
                $stmt->bindValue(':email', $email);
                $stmt->bindValue(':telephone', $telephone);
                $stmt->bindValue(':address', $address);
                $stmt->bindValue(':id', $customer_id);
                $stmt->bindValue(':utilisateur_id', $user_id);
                $result = $stmt->execute();

                if ($result) {
                    $res::status(200);
                    $res::json(array('error' => false, 'message' => 'Customer update', 'data' => []));
                    return;
                } else {
                    $res::status(500);
                    $res::json(array('error' => true, 'message' => 'Internal server error', 'data' => []));
                    return;
                }
            } catch (Throwable $e) {
                $res::status(500);
                $res::json(array('error' => true, 'message' => 'Internal server error', 'data' => []));
                return;
            }
        }
    };
}

function delete_customer()
{


    return function ($req, $res) {
        global $root;

        $db_path = $root . '/' . $_ENV['DB_PATH'];
        $db = new MyDB($db_path);

        $customer_id = isset($req::body()['id']) ? $req::body()['id'] : null;
        $user_id = $req::$headers['user_id'];
        if ($user_id == null) {
            $res::status(401);
            $res::json(array('error' => true, 'message' => 'Unauthorized', 'data' => []));
            return;
        }

        $query = 'DELETE FROM Client WHERE id = :id AND utilisateur_id = :utilisateur_id';

        $stmt = $db->prepare($query);
        $stmt->bindValue(':id', $customer_id);
        $stmt->bindValue(':utilisateur_id', $user_id);
        $result = $stmt->execute();

        if ($result) {
            $res::status(200);
            $res::json(array('error' => false, 'message' => 'Customer deleted', 'data' => []));
            return;
        } else {
            $res::status(500);
            $res::json(array('error' => true, 'message' => 'Internal server error', 'data' => []));
            return;
        }
    };
}


function get_customer()
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
        $query = '';
        $customer_id = isset($req::$params['id']) ? $req::$params['id'] : null;
        if ($customer_id) {
            $query = 'SELECT * FROM Client WHERE id = :id AND utilisateur_id = :utilisateur_id';
        } else {
            $query = 'SELECT * FROM Client WHERE  utilisateur_id = :utilisateur_id';
        }


        try {
            $stmt = $db->prepare($query);
            if ($customer_id) {
                $stmt->bindValue(':id', $customer_id);
            }

            $stmt->bindValue(':utilisateur_id', $user_id);

            $result = $stmt->execute();
            $customer =  [];
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                array_push($customer, $row);
            }
         
            if (!empty($customer)) {
                $res::status(200);
                $res::json(array('error' => false, 'message' => 'Customer found', 'data' => $customer));
                return;
            } else {
                $res::status(404);
                $res::json(array('error' => true, 'message' => 'Customer not found', 'data' => []));
                return;
            }
        } catch (Throwable $th) {
            $res::status(500);
            $res::json(array('error' => true, 'message' => 'Internal server error', 'data' => []));
            return;
        }
    };
}
