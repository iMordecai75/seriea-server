<?php 

class ApiResponse {
    public $status; 

    public $msg;

    public $item;

    public $items;

    public function bind($data) {
        foreach($data as $key => $val) {
            $this->$key = $this->val;
        }
    }

    public function toJson() {
        return json_encode($this);
    }
}