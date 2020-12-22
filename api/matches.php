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