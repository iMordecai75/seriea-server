<?php
/*METHOD GET AND GET WITH GET ID*/

header("Access-Control-Allow-Origin: *");
header("Access-Control-Expose-Headers: Content-Length, X-JSON");
header("Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: *");
header('Content-Type: application/json');

require_once '../classes/AuthResponse.php';
require_once '../utilities/dbconn.php';
require_once '../utilities/utilities.php';

$response = new AuthResponse();

$json = trim(file_get_contents('php://input'));
$input = json_decode(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $json), true);
//Make sure that it is a POST request.
$method = $_SERVER['REQUEST_METHOD'];

/*CONN DB*/
try {
    $dbh = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    $response->msg = 'Errore connessione al database !' . $e->getMessage();
    $response->error = 1;
    echo $response->toJson();
    die();
}
/*END CONN DB*/

switch($method) {
    case 'POST':
        $username = $_REQUEST['username'];
        $password = $_REQUEST['password'];

        if (isset($username) and isset($password)) {

            $fields = array('username' => $username, 'password' => $password);
            // Create token header as a JSON string
            $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
            // Create token payload as a JSON string
            $payload = json_encode($fields);

            // Encode Header to Base64Url String
            $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));

            // Encode Payload to Base64Url String
            $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

            // Create Signature Hash
            $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, 'abC123!', true);

            // Encode Signature to Base64Url String
            $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

            // Create JWT
            $jwt = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
        } else {
            $response->msg  = "Credenziali mancanti";
            $response->error = 1;
            echo $response->toJson();
            exit();
        }

        try {
            $sql = "UPDATE tblUsers SET User_sToken=? WHERE User_sUsername=? AND User_sPassword=?";
            $stmt = $dbh->prepare($sql);
            $stmt->execute([$jwt, $username, $password]);
        } catch (PDOException $e) {
            $response->msg = $e->getMessage();
            $response->error = 1;
            echo $response->toJson();
            exit();
        }

        try {
            $sql = 'SELECT User_sFirstname, User_sLastname, User_sToken, User_iScadenza FROM tblUsers WHERE User_sUsername = :username AND User_sPassword = :password';
            $stmt = $dbh->prepare($sql);
            $stmt->execute(array('username' => $username, 'password' => $password));
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $num_rows = $stmt->rowCount();
        } catch (PDOException $e) {
            $response->msg = 'Errore select tabella !' . $e->getMessage();
            $response->error = 1;
            echo $response->toJson();
            die();
        }
        if ($num_rows == 1) {
            $response->bind($row);
            echo $response->toJson();
        } else {
            $response->msg = "Credenziali errate";
            $response->error = 1;
            echo $response->toJson();
        }
    break;
    case 'PATCH':
        $token = getBearerToken();
        if(empty($token)) {
            $response->msg = 'Token mancante';
            $response->error = 1;
            echo $response->toJson();
        } else {
            try {
                $query = "UPDATE tblUsers SET User_sToken = '' WHERE User_sToken = ?";
                $stmt = $dbh->prepare($query);
                $stmt->execute([$token]);

                $response->status = 'OK';

                echo $response->toJson();
                exit;
            } catch (PDOException $th) {
                $response->status = 'KO';
                $response->msg = $th->getMessage();

                echo $response->toJson();
                exit;
            }
        }
    break;
    default:
break;
}
