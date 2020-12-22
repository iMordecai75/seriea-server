<?php
/*METHOD GET AND GET WITH GET ID*/

header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT,DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization");
header("Content-Type: application/json; charset=UTF-8");
require_once 'classes/Api.php';
require_once 'classes/ApiResponse.php';
require_once 'utilities/connection.php';

class ApiTeams extends Api
{

    function __construct()
    {
        parent::__contruct();
    }

    public function execute()
    {
        $user = null;
        if (empty($this->token)) {
            $this->response->status = 'KO';
            $this->response->msg = 'Token assente';

            echo $this->response->toJson();
            exit;
            
        } else {
            try {
                $query = "SELECT User_iId FROM tblUsers WHERE User_sToken = ?";
                $stmt = $this->dbh->prepare($query);
                $stmt->execute([$this->token]);

                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!($user['User_iId'] > 0)) {
                    $this->response->status = 'KO';
                    $this->response->msg = "Token errato";

                    echo $this->response->toJson();
                    exit;
                }
            } catch (PDOException $th) {
                $this->response->status = 'KO';
                $this->response->msg = $th->getMessage();

                echo $this->response->toJson();
                exit;
            }
        }
        switch ($this->method) {
            case 'GET':
                try {
                    $result = Connection::cURLdownload('/2020-21/it.1.clubs.json');

                    $this->response->status = 'OK';
                    $response = json_decode($result);
                    $this->response->items = $response->clubs;

                    echo $this->response->toJson();
                } catch (\Throwable $th) {
                    $this->response->status = 'KO';
                    $this->response->msg = $th->getMessage();

                    echo $this->response->toJson();
                }
            break;
        }
    }
}

try {
    $teams = new ApiTeams();
    $teams->execute();
} catch (\Throwable $th) {
    $this->response->status = 'KO';
    $this->response->msg = $th->getMessage();

    echo $this->response->toJson();
    die();
}