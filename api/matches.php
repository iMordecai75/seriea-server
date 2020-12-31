<?php
/*METHOD GET AND GET WITH GET ID*/

header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization");
header("Content-Type: application/json; charset=UTF-8");
require_once 'classes/Api.php';
require_once 'classes/ApiResponse.php';
require_once 'utilities/connection.php';

class ApiMatches extends Api
{

    function __construct()
    {
        parent::__contruct();
    }

    public function execute()
    {
        $this->response = new ApiResponse();
        if (empty($this->token)) {
            if($this->method !== 'GET') {
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
                    $query = "SELECT Match_iId, Match_sRound, Match_sDate, Match_sTeam1, Match_sTeam2, Match_iGoal1, Match_iGoal2, Match_iState "
                        . "FROM tblMatches";
                    $stmt = $this->dbh->prepare($query);
                    $stmt->execute();

                    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $this->response->status = 'OK';
                    $this->response->items = $rows;

                    echo $this->response->toJson();
                    exit;
                } catch (PDOException $th) {
                    throw $th;                
                }
            break;
            case 'POST':
                try {
                    $result = Connection::cURLdownload('/2020-21/it.1.json');
                    $response = json_decode($result);
                    $matches = $response->matches;

                    $query = "INSERT INTO tblMatches (Match_sRound, Match_sDate, Match_sTeam1, Match_sTeam2, Match_iGoal1, Match_iGoal2, Match_iState) VALUE (?, ?, ?, ?, ?, ?, ?)";
                    $this->dbh->beginTransaction();
                    foreach($matches as $match) {
                        $stmt = $this->dbh->prepare($query);
                        $goal1 = isset($match->score) ? $match->score->ft[0] : 0;
                        $goal2 = isset($match->score) ? $match->score->ft[1] : 0;
                        $state = isset($match->score) ? 1 : 0;
                        $stmt->execute([$match->round, $match->date, $match->team1, $match->team2, $goal1, $goal2, $state]);
                    }
                    $this->dbh->commit();
                    $this->response->status = 'OK';

                    echo $this->response->toJson();
                    exit;
                } catch (PDOException $th) {
                    throw $th;                
                }
            break;
            case 'PATCH':                
                //Questo metodo servirà per aggiornare il risultato della partita;
                try {
                    $query = "UPDATE tblMatches SET Match_iGoal1 = ?, Match_iGoal2 = ? WHERE Match_iId = ?";
                    if(isset($this->input['data'])) {
                        $data = json_decode($this->input['data']);
                        foreach($data as $match) {
                            $stmt = $this->dbh->prepare($query);
                            $stmt->execute([$match->Match_iGoal1, $match->Match_iGoal2, $match->Match_iId]);
                        }
                    }
                    $query = "SELECT Match_iId, Match_sRound, Match_sDate, Match_sTeam1, Match_sTeam2, Match_iGoal1, Match_iGoal2, Match_iState "
                    . "FROM tblMatches";
                    $stmt = $this->dbh->prepare($query);
                    $stmt->execute();

                    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $this->response->status = 'OK';
                    $this->response->items = $rows;

                    echo $this->response->toJson();
                    exit;
                } catch (PDOException $th) {
                    throw $th;
                }
            break;
        }
    }
}

try {
    $matches = new ApiMatches();
    $matches->execute();
} catch (\Throwable $th) {
    $response = new ApiResponse();
    $response->status = 'KO';
    $esponse->msg = $th->getMessage();

    echo $response->toJson();
    die();
}