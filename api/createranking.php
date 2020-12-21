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

class ApiCreateRanking extends Api {

    function __construct()
    {
        parent::__contruct();
    }
    public function execute() {
        if (empty($token)) {
            $this->response->status = 'KO';
            $this->response->msg = "Token assente";

            echo $this->response->toJson();
            die();
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

        switch($this->method) {
            case 'GET':
                try {
                    $result = Connection::cURLdownload('/2020-21/it.1.json');
                    // faccio il truncate della table
                    // aggiungo le squadre
                    // poi calcolo i punti e i gol
                } catch (\Throwable $th) {
                    //throw $th;
                }
                break;                
        }
    }
}

$ranking = new ApiCreateRanking();
$ranking->execute();