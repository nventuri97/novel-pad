<?php
    class User{
        private $id;
        private $username;
        private $email;
        private $full_name;
        private $is_premium;

        function __construct($id, $username, $email, $full_name, $is_premium){
            $this->id = $id;
            $this->username = $username;
            $this->email = $email;
            $this->full_name = $full_name;
            $this->is_premium = $is_premium;
        }

        public function to_array() {
            return [
                "id" => $this->id,
                "username" => $this->username,
                "full_name" => $this->full_name,
                "email" => $this->email,
                "is_premium" => $this->is_premium,
            ];
        }

        function get_id(){
            return $this->id;
        }

        function get_username(){
            return $this->username;
        }

        function get_email(){
            return $this->email;
        }
        
        function get_full_name(){
            return $this->full_name;
        }

        function is_premium(){
            return $this->is_premium;
        }

        function set_premium($is_premium){
            $this->is_premium = $is_premium;
        }

        function set_full_name($full_name){
            $this->full_name = $full_name;
        }

        function set_email($email){
            $this->email = $email;
        }

        function set_username($username){
            $this->username = $username;
        }

        function set_id($id){
            $this->id = $id;
        }
    }
?>