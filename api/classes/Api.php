<?php

class Api {

    protected $response;
    protected $method;
    protected $dbh;
    protected $token;

    function __contruct() {
        
        $host = '62.149.150.110';
        $dbname = 'Sql319510_4';
        $user = 'Sql319510';
        $pass = 'a296210b';


        $host = 'localhost';
        $dbname = 'seriea';
        $user = 'root';
        $pass = 'root';

        $this->response = new ApiResponse();
        $json = trim(file_get_contents('php://input'));
        parse_str($json, $input);

        $this->method = $_SERVER['REQUEST_METHOD'];

        try {
            $this->dbh = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
            $this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            throw $e;
        }

        $this->token = $this->getBearerToken();
    }

    /** 
     * Get header Authorization
     * */
    function getAuthorizationHeader()
    {
        $headers = null;
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER["Authorization"]);
        } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            //print_r($requestHeaders);
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
        return $headers;
    }
    /**
     * get access token from header
     * */
    function getBearerToken()
    {
        $headers = $this->getAuthorizationHeader();        
        // HEADER: Get the access token from the header
        if (!empty($headers)) {
            if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }    
}