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

        $type = isset($req::$query['type']) ? $req::$query['type'] : null; // client || fournisseur
        $status = isset($req::$query['status']) ? $req::$query['status'] : null; // en_cours || payee || annulee
        $invoice_id = isset($req::$params['id']) ? $req::$params['id'] : null;

        $query = <<<SQLQ
            SELECT 
            Facture.id AS facture_id,
            Facture.numero,
            Facture.date_emission,
            Facture.date_echeance,
            Facture.montant_total,
            Facture.status,
            Client.id AS client_id,
            Client.nom AS client_nom,
            Client.email AS client_email,
            Client.telephone AS client_telephone,
            Client.adresse AS client_adresse
        FROM 
            Facture_Client
        INNER JOIN 
            Facture ON Facture_Client.facture_id = Facture.id
        INNER JOIN 
            Client ON Client.utilisateur_id = $user_id
        SQLQ;

        $data = [];
        if ($status) {
            $query .= " WHERE Facture.status = '$status'";
        }
        if($invoice_id){
            $query .= " WHERE Facture.id = $invoice_id";
        }
        try {
            $stmt = $db->prepare($query);
            $result = $stmt->execute();
            if (!$result) {
                $res::status(500);
                $res::json(array('error' => true, 'message' => 'Internal server error', 'data' => []));
                return;
            } else {
                while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                    array_push($data, $row);
                }

                if (empty($data)) {
                    $res::status(404);
                    $res::json(array('error' => true, 'message' => 'Invoice not found', 'data' => []));
                    return;
                } else {
                    $res::status(200);
                    $res::json(array('error' => false, 'message' => 'Invoice found', 'data' => $data));
                    return;
                }
            }
        } catch (Throwable $th) {
            $res::status(500);
            $res::json(array('error' => true, 'message' => 'Internal server error', 'data' => []));
            return;
        }
    };
}

function update_invoice() {
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

        $invoice_id = $req::$params['id']; // Assumes invoice ID is passed as a URL parameter
        $body = $req::body();

        // Build the SQL update query dynamically
        $fields_to_update = [];
        $query_params = [];

        if (isset($body['date_echeance'])) {
            $fields_to_update[] = 'date_echeance = :date_echeance';
            $query_params[':date_echeance'] = $body['date_echeance'];
        }
        if (isset($body['montant_total'])) {
            $fields_to_update[] = 'montant_total = :montant_total';
            $query_params[':montant_total'] = $body['montant_total'];
        }
        if (isset($body['status'])) {
            $fields_to_update[] = 'status = :status';
            $query_params[':status'] = $body['status'];
        }

        // Only proceed if there's something to update
        if (!empty($fields_to_update)) {
            $update_query = 'UPDATE Facture SET ' . implode(', ', $fields_to_update) . ' WHERE id = :invoice_id AND utilisateur_id = :user_id';
            $query_params[':invoice_id'] = $invoice_id;
            $query_params[':user_id'] = $user_id;

            try {
                $stmt = $db->prepare($update_query);
                foreach ($query_params as $param => $value) {
                    $stmt->bindValue($param, $value);
                }
                $result = $stmt->execute();

                if ($result) {
                    // Cas d'une Facture payé
                    if (isset($body['status']) && $body['status'] == 'payee') {
                        $wallet_id = isset($body['wallet_id']) ? $body['wallet_id'] : null;
                        $type_trans = isset($body['type_trans']) ? $body['type_trans'] : null;

                        if ($wallet_id && $type_trans) {
                            $wallet = $db->querySingle("SELECT * FROM Portefeuille WHERE id = $wallet_id", true);
                            $new_amount = $type_trans == 'entree' ? $wallet['solde'] + $body['montant_total'] : $wallet['solde'] - $body['montant_total'];

                            if ($new_amount < 0) {
                                /* Suppression de la mise à jour si erreur */
                                $db->exec("ROLLBACK");
                                $res::status(400);
                                $res::json(array('error' => true, 'message' => 'Insufficient balance', 'data' => []));
                                return;
                            }

                            $query = 'UPDATE Portefeuille SET solde = :solde WHERE id = :id';
                            $stmt = $db->prepare($query);
                            $stmt->bindValue(':solde', $new_amount);
                            $stmt->bindValue(':id', $wallet_id);
                            $result = $stmt->execute();

                            if ($result) {
                                $query = 'INSERT INTO Transactions (facture_id, montant, type, portefeuille_id)
                                VALUES(:facture_id, :montant, :type_trans, :portefeuille_id)';
                                
                                $stmt = $db->prepare($query);
                                $stmt->bindValue(':facture_id', $invoice_id);
                                $stmt->bindValue(':montant', $body['montant_total']);
                                $stmt->bindValue(':type_trans', $type_trans);
                                $stmt->bindValue(':portefeuille_id', $wallet_id);
                                $result = $stmt->execute();

                                if (!$result) {
                                    $res::status(500);
                                    $res::json(array('error' => true, 'message' => 'Internal server error during transaction creation', 'data' => []));
                                    return;
                                }
                            } else {
                                $res::status(500);
                                $res::json(array('error' => true, 'message' => 'Internal server error during wallet update', 'data' => []));
                                return;
                            }
                        }
                    }

                    $data = $db->querySingle("SELECT * FROM Facture WHERE id = $invoice_id", true);
                    $res::status(200);
                    $res::json(array('error' => false, 'message' => 'Invoice updated successfully', 'data' => $data));
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
        } else {
            $res::status(400);
            $res::json(array('error' => true, 'message' => 'No data provided for update', 'data' => []));
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
