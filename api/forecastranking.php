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

class ApiForecastRanking extends Api
{

    function __construct()
    {
        parent::__contruct();
    }

    public function execute()
    {
        $this->response = new ApiResponse();
        if (empty($this->token)) {
            if ($this->method !== 'GET') {
                $this->response->msg = "Token assente";
                $this->method = 'GET';
            }
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
                throw $th;
            }
        }

        switch ($this->method) {
            case 'GET':
                try {

                    $query = "SELECT 
                            Ranking_sTeam,
                            Ranking_iPunti
                            FROM tblRanking 
                            ORDER BY Ranking_iPunti DESC, Ranking_iGiocate ASC
                            LIMIT 0, 6";

                    $stmt = $this->dbh->prepare($query);
                    $stmt->execute();

                    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    $query1 = 'SELECT 10 AS punti, User_iId FROM tblForecasts WHERE Forecast_iPosition = 1 AND Forecast_sTeam = ?';
                    $query2 = 'SELECT 6 AS punti, User_iId FROM tblForecasts WHERE Forecast_iPosition = 2 AND Forecast_sTeam = ?';
                    $query3 = 'SELECT 5 AS punti, User_iId FROM tblForecasts WHERE Forecast_iPosition = 3 AND Forecast_sTeam = ?';
                    $query4 = 'SELECT 4 AS punti, User_iId FROM tblForecasts WHERE Forecast_iPosition = 4 AND Forecast_sTeam = ?';
                    $query5 = 'SELECT 3 AS punti, User_iId FROM tblForecasts WHERE Forecast_iPosition = 5 AND Forecast_sTeam = ?';
                    $query6 = 'SELECT 2 AS punti, User_iId FROM tblForecasts WHERE Forecast_iPosition = 6 AND Forecast_sTeam = ?';
                    $query14 = 'SELECT 2 AS punti, User_iId FROM tblForecasts WHERE Forecast_iPosition IN (1,2,3,4) AND Forecast_sTeam IN (?, ?, ?, ?)';
                    $query56 = 'SELECT 1 AS punti, User_iId FROM tblForecasts WHERE Forecast_iPosition IN (5,6) AND Forecast_sTeam IN (?, ?)';

                    $subquery = $query1 . ' UNION ' . $query2 . ' UNION ' . $query3 . ' UNION ' . $query4 . ' UNION ' . $query5 . ' UNION '
                        . $query6 . ' UNION ' . $query14 . ' UNION ' . $query56;

                    $query = 'SELECT t.User_iId, User_sFirstname, User_sLastname, SUM(punti) as punti FROM (' . $subquery . ') AS t '
                        . 'INNER JOIN tblUsers ON tblUsers.User_iId = t.User_iId GROUP BY User_iId ORDER BY punti DESC';

                    $stmt = $this->dbh->prepare($query);
                    $stmt->execute(
                        [
                            $rows[0]['Ranking_sTeam'],
                            $rows[1]['Ranking_sTeam'],
                            $rows[2]['Ranking_sTeam'],
                            $rows[3]['Ranking_sTeam'],
                            $rows[4]['Ranking_sTeam'],
                            $rows[5]['Ranking_sTeam'],
                            $rows[0]['Ranking_sTeam'],
                            $rows[1]['Ranking_sTeam'],
                            $rows[2]['Ranking_sTeam'],
                            $rows[3]['Ranking_sTeam'],
                            $rows[4]['Ranking_sTeam'],
                            $rows[5]['Ranking_sTeam'],
                        ]
                    );                    
                    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $this->response->status = 'OK';
                    $this->response->items = $result;

                    echo $this->response->toJson();
                    exit;
                    
                } catch (\Throwable $th) {
                    throw $th;
                }

                break;
        }
    }
}

try {
    $matches = new ApiForecastRanking();
    $matches->execute();
} catch (\Throwable $th) {
    $response = new ApiResponse();
    $response->status = 'KO';
    $response->msg = $th->getMessage();

    echo $response->toJson();
    die();
}
