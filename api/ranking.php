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
        $this->response = new ApiResponse();
        if (empty($this->token)) {
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
                    $query = "SELECT Ranking_sTeam, Ranking_iGiocate, Ranking_iGoalFatti, Ranking_iGoalSubiti, Ranking_iPunti "
                        . "FROM tblRanking ORDER BY Ranking_iPunti DESC, Ranking_iGiocate ASC";
                    $stmt = $this->dbh->prepare($query);
                    $stmt->execute();

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
            break;
            case 'POST':
                try {                                        
                    $query = "TRUNCATE tblRanking";
                    $this->dbh->beginTransaction();
                    $stmt = $this->dbh->prepare($query);
                    $stmt->execute();
                    $queryHome = "SELECT `Match_sRound`, tblMatches.`Match_sTeam1` AS Team, "
                        . "IF(`Match_iGoal1`>`Match_iGoal2`,3,IF(`Match_iGoal1`<`Match_iGoal2`,0,1)) as P, "
                        . "1 AS G, `Match_iGoal1` as GF, `Match_iGoal2` AS GS, (`Match_iGoal1` - `Match_iGoal2`) AS DR "
                        . "FROM tblMatches "
                        . "INNER JOIN (SELECT DISTINCT Match_sTeam1 FROM tblMatches) AS m ON m.`Match_sTeam1` = tblMatches.Match_sTeam1 "
                        . "WHERE tblMatches.`Match_iState` = 1";
                    $queryAway = "SELECT `Match_sRound`, tblMatches.`Match_sTeam2` AS Team, "
                        . "IF(`Match_iGoal1`>`Match_iGoal2`,0,IF(`Match_iGoal1`<`Match_iGoal2`,3,1)) as P, "
                        . "1 AS G, `Match_iGoal2` as GF, `Match_iGoal1` AS GS, (`Match_iGoal2` - `Match_iGoal1`) AS DR "
                        . "FROM tblMatches "
                        . "INNER JOIN (SELECT DISTINCT Match_sTeam1 FROM tblMatches) AS m ON m.`Match_sTeam1` = tblMatches.Match_sTeam2 "
                        . "WHERE tblMatches.`Match_iState` = 1";
                    $querySelect = "SELECT null AS id, Team, SUM(G) AS PG, SUM(GF) AS GF, SUM(GS) AS GS, SUM(P) AS P  "
                        . "FROM (" . $queryHome . " UNION " . $queryAway . ") AS t "
                        . "GROUP BY Team ORDER BY P DESC, PG ASC";

                    $query = "INSERT INTO tblRanking " . $querySelect;
                    
                    $stmt = $this->dbh->prepare($query);
                    $stmt->execute();
                    $this->dbh->commit();
                    $this->response->status = 'OK';

                    echo $this->response->toJson();
                    exit;
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
    $ranking = new ApiCreateRanking();
    $ranking->execute();
} catch (\Throwable $th) {
    $response = new ApiResponse();
    $response->status = 'KO';
    $esponse->msg = $th->getMessage();

    echo $response->toJson();
    die();
}