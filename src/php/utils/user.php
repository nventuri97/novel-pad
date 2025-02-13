<?php
    class User{
        private $id;
        private $email;
        private $nickname;
        private $is_premium;

        function __construct($id, $email, $nickname, $is_premium){
            $this->id = $id;
            $this->email = $email;
            $this->nickname = $nickname;
            $this->is_premium = $is_premium;
        }

        public function to_array() {
            return [
                "id" => $this->id,
                "email" => $this->email,
                "nickname" => $this->nickname,
                "is_premium" => $this->is_premium
            ];
        }

        function get_id(){
            return $this->id;
        }

        function get_email(){
            return $this->email;
        }
        
        function get_nickname(){
            return $this->nickname;
        }

        function is_premium(){
            return $this->is_premium;
        }

        function set_premium($is_premium){
            $this->is_premium = $is_premium;
        }

        function set_nickname($nickname){
            $this->nickname = $nickname;
        }

        function set_email($email){
            $this->email = $email;
        }

        function set_id($id){
            $this->id = $id;
        }
    }
?>