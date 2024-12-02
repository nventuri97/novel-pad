<?php
    class Novel{
        private $id;
        private $title;
        private $description;
        private $author;
        private $genre;
        private $type;
        private $file_path;
        private $is_premium;
        private $uploaded_at;

        function __construct($id, $title, $description, $author, $genre, $type, $file_path, $is_premium, $uploaded_at){
            $this->id = $id;
            $this->title = $title;
            $this->description = $description;
            $this->author = $author;
            $this->genre = $genre;
            $this->type = $type;
            $this->file_path = $file_path;
            $this->is_premium = $is_premium;
            $this->uploaded_at = $uploaded_at;
        }

        public function to_array() {
            return [
                "id" => $this->id,
                "title" => $this->title,
                "description" => $this->description,
                "author" => $this->author,
                "genre" => $this->genre,
                "type" => $this->type,
                "file_path" => $this->file_path,
                "is_premium" => $this->is_premium,
                "uploaded_at" => $this->uploaded_at
            ];
        }

        function get_id(){
            return $this->id;
        }

        function get_title(){
            return $this->title;
        }
        
        function get_description(){
            return $this->description;
        }

        function get_author(){
            return $this->author;
        }

        function get_genre(){
            return $this->genre;
        }

        function get_type(){
            return $this->type;
        }

        function get_file_path(){
            return $this->file_path;
        }

        function is_premium(){
            return $this->is_premium;
        }

        function get_uploaded_at(){
            return $this->uploaded_at;
        }

        function set_id($id){
            $this->id = $id;
        }

        function set_title($title){
            $this->title = $title;
        }

        function set_description($description){
            $this->description = $description;
        }

        function set_author($author){
            $this->author = $author;
        }

        function set_genre($genre){
            $this->genre = $genre;
        }

        function set_type($type){
            $this->type = $type;
        }

        function set_file_path($file_path){
            $this->file_path = $file_path;
        }

        function set_premium($is_premium){
            $this->is_premium = $is_premium;
        }

        function set_uploaded_at($uploaded_at){
            $this->uploaded_at = $uploaded_at;
        }
    }
?>