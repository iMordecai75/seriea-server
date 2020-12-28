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

class ApiMatches extends Api
{

    function __construct()
    {
        parent::__contruct();
    }

    public function execute()
    {
        $this->response = new ApiResponse();
        switch ($this->method) {
            case 'GET':
                try {
                    // Va modificato con una select sul db;
                    $result = Connection::cURLdownload('/2020-21/it.1.json');

                    $this->response->status = 'OK';
                    $response = json_decode($result);
                    $this->response->items = $response->matches;

                    echo $this->response->toJson();
                } catch (\Throwable $th) {
                    $this->response->status = 'KO';
                    $this->response->msg = $th->getMessage();

                    echo $this->response->toJson();
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
                } catch (\Throwable $th) {
                    $this->dbh->rollBack();
                    $this->response->status = 'KO';
                    $this->response->msg = $th->getMessage();

                    echo $this->response->toJson();
                    exit;
                }
            break;
            case 'PATCH':
                //Questo metodo servirà per inserire il risultato della partita;
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