<?php

function add_wallet()
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
        $name = isset($body['name']) ? $body['name'] : null;
        $solde = isset($body['solde']) ? $body['solde'] : null;
        $desc = isset($body['desc']) ? $body['desc'] : null;

        if (empty($name) || empty($solde)) {
            $res::status(400);
            $res::json(array('error' => true, 'message' => 'You must provide a name and a solde', 'data' => []));
            return;
        }

        $query = 'INSERT INTO Portefeuille (nom, solde, description, utilisateur_id) VALUES (:nom, :solde, :desc, :utilisateur_id)';
        try {
            $stmt = $db->prepare($query);
            $stmt->bindValue(':nom', $name);
            $stmt->bindValue(':solde', $solde);
            $stmt->bindValue(':desc', $desc);
            $stmt->bindValue(':utilisateur_id', $user_id);
            $result = $stmt->execute();
            if ($result) {
                $res::status(200);
                $res::json(array('error' => false, 'message' => 'Wallet created', 'data' => []));
                return;
            }
        } catch (Throwable $e) {
            $res::status(500);
            $res::json(array('error' => true, 'message' => 'Internal server error', 'data' => []));
            return;
        }
    };
}


function update_wallet()
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

        $name = isset($body['name']) ? $body['name'] : null;
        $solde = isset($body['solde']) ? $body['solde'] : null;
        $desc = isset($body['desc']) ? $body['desc'] : null;
        $wallet_id = isset($body['wallet_id']) ? $body['wallet_id'] : null;

        $query = 'UPDATE Portefeuille SET ';

        if (!empty($name)) {
            $query .='nom = "' . $name . '",';
        }
        if (!empty($solde)) {
            $query .= 'solde = "' . $solde . '",';
        }
        if (!empty($desc)) {
            $query .= 'description = "' . $desc . '",';
        }

        $query .= " WHERE id = " .$wallet_id. " AND utilisateur_id = " .$user_id . "";
       
        error_log('Query : ' .$query);
        try {
            $result = $db->exec($query);
            if ($result) {
                $res::status(200);
                $res::json(array('error' => false, 'message' => 'Wallet updated', 'data' => []));
                return;
            }
        } catch (Throwable $e) {
            $res::status(500);
            $res::json(array('error' => true, 'message' => 'Internal server error', 'data' => []));
            return;
        }
    };
}

function delete_wallet()
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

        $wallet_id = $body['wallet_id'];

        if (empty($wallet_id)) {
            $res::status(400);
            $res::json(array('error' => true, 'message' => 'You must provide a wallet id', 'data' => []));
            return;
        }

        $query = 'DELETE FROM Portefeuille WHERE id = :id AND utilisateur_id = :utilisateur_id';
        try {
            $stmt = $db->prepare($query);
            $stmt->bindValue(':id', $wallet_id);
            $stmt->bindValue(':utilisateur_id', $user_id);
            $result = $stmt->execute();
            if ($result) {
                $res::status(200);
                $res::json(array('error' => false, 'message' => 'Wallet deleted', 'data' => []));
                return;
            } else {
                $res::status(500);
                $res::json(array('error' => true, 'message' => 'Internal server error', 'data' => []));
                return;
            }
        } catch (Throwable $e) {
            $res::status(500);
            error_log('Error : ' . $e->getMessage());
            $res::json(array('error' => true, 'message' => 'Internal server error', 'data' => []));
            return;
        }
    };
}

function get_wallets()
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

        $id_wallet = isset($req::$params['id']) ? $req::$params['id'] : null;

        $db = new MyDB($db_path);

        $query = 'SELECT * FROM Portefeuille WHERE utilisateur_id = :utilisateur_id';
        if (!empty($id_wallet)) {
            $query .= ' AND id = :id';
        }
        $data = [];
        try {
            $stmt = $db->prepare($query);
            $stmt->bindValue(':utilisateur_id', $user_id);
            if (!empty($id_wallet)) {
                $stmt->bindValue(':id', $id_wallet);
            }
            $result = $stmt->execute();
            if ($result) {
                while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                    array_push($data, $row);
                }
                $res::status(200);
                if(!empty($id_wallet)){
                    $data = $data[0];
                }
                $res::json(array('error' => false, 'message' => 'Wallets retrieved', 'data' => $data));
                return;
            }
        } catch (Throwable $th) {
            $res::status(500);
            $res::json(array('error' => true, 'message' => 'Internal server error', 'data' => []));
            return;
        }
    };
}
