<?php
/*METHOD GET AND GET WITH GET ID*/

header("Access-Control-Allow-Origin: *");
header("Access-Control-Expose-Headers: Content-Length, X-JSON");
header("Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: *");
header('Content-Type: application/json');
require_once '../classes/Api.php';
require_once '../classes/AuthResponse.php';

class ApiAuth extends Api
{
    function __construct()
    {
        parent::__contruct();

        $this->response = new AuthResponse();
    }

    public function execute()
    {
        switch ($this->method) {
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
                    $this->response->msg  = "Credenziali mancanti";
                    $this->response->error = 1;
                    echo $this->response->toJson();
                    exit();
                }

                try {
                    $sql = "UPDATE tblUsers SET User_sToken=? WHERE User_sUsername=? AND User_sPassword=?";
                    $stmt = $this->dbh->prepare($sql);
                    $stmt->execute([$jwt, $username, $password]);
                } catch (PDOException $e) {
                    $this->response->msg = $e->getMessage();
                    $this->response->error = 1;

                    echo $this->response->toJson();
                    exit();
                }

                try {
                    $sql = 'SELECT User_sFirstname, User_sLastname, User_sToken, User_iScadenza FROM tblUsers WHERE User_sUsername = :username AND User_sPassword = :password';
                    $stmt = $this->dbh->prepare($sql);
                    $stmt->execute(array('username' => $username, 'password' => $password));
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    $num_rows = $stmt->rowCount();
                } catch (PDOException $e) {
                    $this->response->msg = 'Errore select tabella !' . $e->getMessage();
                    $this->response->error = 1;

                    echo $this->response->toJson();
                    die();
                }
                if ($num_rows == 1) {
                    $this->response->bind($row);

                    echo $this->response->toJson();
                } else {
                    $this->response->msg = "Credenziali errate";
                    $this->response->error = 1;

                    echo $this->response->toJson();
                }
                break;
            case 'PATCH':
                if (empty($this->token)) {
                    $this->response->msg = 'Token mancante';
                    $this->response->error = 1;

                    echo $this->response->toJson();
                } else {
                    try {
                        $query = "UPDATE tblUsers SET User_sToken='' WHERE User_sToken=?";
                        $stmt = $this->dbh->prepare($query);
                        $stmt->execute([$this->token]);

                        $this->response->status = 'OK';

                        echo $this->response->toJson();
                        exit;
                    } catch (PDOException $th) {
                        $this->response->status = 'KO';
                        $this->response->msg = $th->getMessage();

                        echo $this->response->toJson();
                        exit;
                    }
                }
                break;
            default:
                break;
        }
    }

    //$json = trim(file_get_contents('php://input'));
    //$input = json_decode(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $json), true);
    //Make sure that it is a POST request.
}

try {
    $auth = new ApiAuth();
    $auth->execute();
} catch (\Throwable $th) {
    $response = new AuthResponse();
    $response->error = 1;
    $response->msg = $th->getMessage();

    echo $response->toJson();
    die();
}