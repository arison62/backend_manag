<?php
$root = $_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__);

require_once $root . '/models/db.php';

function add_invoice()
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

        $date_echeance = isset($body['date_echeance']) ? $body['date_echeance'] : null;
        $montant_total = isset($body['montant_total']) ? $body['montant_total'] : null;
        $type = isset($body['type']) ? $body['type'] : null; // client || fournisseur
        $status = isset($body['status']) ? $body['status'] : null; // en_cours || payee || annulee
        $client_id = isset($body['client_id']) ? $body['client_id'] : null;
        $wallet_id = isset($body['wallet_id']) ? $body['wallet_id'] : null;
        $type_trans = isset($body['type_trans']) ? $body['type_trans'] : null; // type de transaction depense || entree


        $query = 'INSERT INTO Facture (date_echeance, montant_total,
         type, status, utilisateur_id) VALUES (:date_echeance, :montant_total, :type, :status, :utilisateur_id)';
        try {
            $stmt = $db->prepare($query);
            $stmt->bindValue(':date_echeance', $date_echeance);
            $stmt->bindValue(':montant_total', $montant_total);
            $stmt->bindValue(':type', $type);
            $stmt->bindValue(':status', $status);
            $stmt->bindValue(':utilisateur_id', $user_id);
            $result = $stmt->execute();
            if ($result) {
                $invoice_id = $db->lastInsertRowID();
                $data = $db->querySingle("SELECT * FROM Facture WHERE id = $invoice_id", true);

                if ($type == 'client') {
                    
                    // Facture payee  donnant lieu a une transaction
                    if ($status == 'payee') {

                        $wallet = $db->querySingle("SELECT * FROM Portefeuille WHERE id = $wallet_id", true);
                        $new_amount = $type_trans == 'entree' ? $wallet['solde'] + $montant_total :  $wallet['solde'] - $montant_total;
                        if ($new_amount < 0) {
                            /* Suppression de la facture si erreur */
                            $db->exec("DELETE FROM Facture WHERE id = $invoice_id");
                            $res::status(400);
                            $res::json(array('error' => true, 'message' => 'Insufficient balance', 'data' => []));
                            return;
                        }

                        $query = 'UPDATE Portefeuille SET solde = :solde WHERE id = :id';
                        $stmt = $db->prepare($query);
                        $stmt->bindValue(':solde', $new_amount);
                        $stmt->bindValue(':id', $wallet_id);
                        $result = $stmt->execute();

                        if (!$result) {
                            $res::status(500);
                            $res::json(array('error' => true, 'message' => 'Internal server error', 'data' => []));
                        } else {

                            if ($status == 'payee') {
                                $query = 'INSERT INTO Transactions (facture_id, montant, type, portefeuille_id)
                                VALUES(:facture_id, :montant, :type_trans, :portefeuille_id)';

                                $stmt = $db->prepare($query);
                                $stmt->bindValue(':facture_id', $invoice_id);
                                $stmt->bindValue(':montant', $montant_total);
                                $stmt->bindValue(':type_trans', $type_trans);
                                $stmt->bindValue(':portefeuille_id', $wallet_id);
                                $result = $stmt->execute();

                                if (!$result) {
                                    $res::status(500);
                                    $res::json(array('error' => true, 'message' => 'Internal server error', 'data' => []));
                                    return;
                                } else {

                                    $res::status(200);
                                    $res::json(array('error' => false, 'message' => 'Invoice created', 'data' => $data));
                                    return;
                                }
                            }
                        }
                    }
                    $query = 'INSERT INTO Facture_Client(facture_id, client_id) VALUES(:facture_id, :client_id)';
                    $stmt = $db->prepare($query);
                    $stmt->bindValue(':facture_id', $invoice_id);
                    $stmt->bindValue(':client_id', $client_id);
                    $result = $stmt->execute();

                    if (!$result) {
                        /* Suppression de la facture si erreur */
                        $db->exec("DELETE FROM Facture WHERE id = $invoice_id");
                        $res::status(500);
                        $res::json(array('error' => true, 'message' => 'Internal server error', 'data' => []));
                        return;
                    } else {
                        $res::status(200);
                        $res::json(array('error' => false, 'message' => 'Invoice created', 'data' => $data));
                        return;
                    }
                }
            }
        } catch (Throwable $th) {
            $res::status(500);
            $res::json(array('error' => true, 'message' => 'Internal server error', 'data' => []));
            return;
        }
    };
}

function get_invoice()
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

        $invoice_id = isset($req::$params['id']) ? $req::$params['id'] : null;
        $data = [];
        $query = 'SELECT * FROM Facture WHERE utilisateur_id = :utilisateur_id';
        if ($invoice_id) {
            $query .= ' AND id = :invoice_id';
        }

        try {
            $stmt = $db->prepare($query);
            $stmt->bindValue(':utilisateur_id', $user_id);
            if ($invoice_id) {
                $stmt->bindValue(':invoice_id', $invoice_id);
            }

            $result = $stmt->execute();


            if ($result) {
                while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                    array_push($data, $row);
                }

                if ($invoice_id) {
                    $data = $data[0];
                }
                $res::status(200);
                $res::json(array('error' => false, 'message' => 'Invoice retrieved', 'data' => $data));
                return;
            } else {
                $res::status(500);
                return;
            }
        } catch (Throwable $th) {

            $res::status(500);
            $res::json(array('error' => true, 'message' => 'Internal server error', 'data' => []));
            return;
        }
    };
}

function update_invoice()
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

        $date_echeance = isset($body['date_echeance']) ? $body['date_echeance'] : null;
        $montant_total = isset($body['montant_total']) ? $body['montant_total'] : null;
        $type = isset($body['type']) ? $body['type'] : null; // client || fournisseur
        $status = isset($body['status']) ? $body['status'] : null; // en_cour || payee || annulee

        $invoice_id = isset($body['invoice_id']) ? $body['invoice_id'] : null;

        $query = 'UPDATE Facture SET ';

        if (!empty($date_echeance)) {
            $query .= 'date_echeance = "' . $date_echeance . '",';
        }
        if (!empty($montant_total)) {
            $query .= 'montant_total = "' . $montant_total . '",';
        }
        if (!empty($type)) {
            $query .= 'type = "' . $type . '",';
        }
        if (!empty($status)) {
            $query .= 'status = "' . $status . '",';
        }

        $query = rtrim($query, ',');

        $query .= " WHERE id = " . $invoice_id . " AND utilisateur_id = " . $user_id . "";

        try {
            $result = $db->exec($query);
            if ($result) {
                $res::status(200);
                $res::json(array('error' => false, 'message' => 'Invoice updated', 'data' => []));
                return;
            } else {
                $res::status(500);
                $res::json(array('error' => true, 'message' => 'Internal server error', 'data' => []));
                return;
            }
        } catch (Throwable $th) {
            $res::status(500);
            $res::json(array('error' => true, 'message' => 'Internal server error', 'data' => []));
            return;
        }
    };
}

function delete_invoice()
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

        $invoice_id = isset($body['invoice_id']) ? $body['invoice_id'] : null;

        $query = 'DELETE FROM Facture WHERE id = ' . $invoice_id . ' AND utilisateur_id = ' . $user_id . '';
        try {
            $result = $db->exec($query);
            if ($result) {
                $res::status(200);
                $res::json(array('error' => false, 'message' => 'Invoice deleted', 'data' => []));
                return;
            } else {
                $res::status(500);
                $res::json(array('error' => true, 'message' => 'Internal server error', 'data' => []));
                return;
            }
        } catch (Throwable $th) {
            $res::status(500);
            $res::json(array('error' => true, 'message' => 'Internal server error', 'data' => []));
            return;
        }
    };
}
