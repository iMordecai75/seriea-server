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

class ApiForecasts extends Api
{

    function __construct()
    {
        parent::__contruct();
    }

    public function execute()
    {
        $user = null;
        if (empty($this->token)) {
            $this->method = 'GET';
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
                    echo 'Token Assente';
                } catch (\Throwable $th) {
                    $this->response->status = 'KO';
                    $this->response->msg = $th->getMessage();

                    echo $this->response->toJson();
                    exit;
                }
            break;
            case 'POST':
                try {
                    $error = array();
                    $query = "INSERT INTO tblForecasts (User_iId, Forecast_iPosition, Forecast_sTeam) VALUES (?, ?, ?)";
                    $this->dbh->beginTransaction();
                    for($i = 1; $i < 7; $i++) {
                        $team = $_POST['team' . $i];
                        $stmt = $this->dbh->prepare($query);
                        if(!$stmt->execute([$user['User_iId'], $i, $team])) {
                            $error[] = 'Error when insert ' . $team . ' at position ' . $i;
                        }
                    }  
                    $this->dbh->commit();
                    if (count($error) > 0) {
                        $this->response->status = 'KO';
                        $this->response->msg = implode(', ', $error);

                        echo $this->response->toJson();
                        exit;
                    } else {
                        $query = "SELECT Forecast_iPosition, Forecast_sTeam FROM tblForecasts WHERE User_iId = ? ORDER BY Forecast_iPosition";
                        try {
                            $stmt = $this->dbh->prepare($query);
                            $stmt->execute([$user['User_iId']]);
                            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            $this->response->status = 'OK';
                            $this->response->items = $rows;

                            echo $this->response->toJson();
                            exit;
                        } catch (\Throwable $th) {
                            $this->response->status = 'KO';
                            $this->response->msg = $th->getMessage();

                            echo $this->response->toJson();
                            exit;
                        }
                    }

                } catch (\Throwable $th) {
                    $this->dbh->rollBack();
                    $this->response->status = 'KO';
                    $this->response->msg = $th->getMessage();

                    echo $this->response->toJson();
                    exit;
                }
            break;
        }
    }
}

try {
    $forecasts = new ApiForecasts();
    $forecasts->execute();    
} catch (\Throwable $th) {
    $this->response->status = 'KO';
    $this->response->msg = $th->getMessage();

    echo $this->response->toJson();
    die();

}