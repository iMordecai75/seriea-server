<?php 

class AuthResponse {
    public $User_sFirstname;
    public $User_sLastname;
    public $User_sToken;
    public $User_iScadenza;
    public $error;
    public $msg;

    public function bind($data) {
        foreach($data as $key => $val) {
            $this->$key = $val;
        }
    }

    public function toJson() {
        return json_encode($this);
    }
}