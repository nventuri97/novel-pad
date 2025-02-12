<?php
// src/admin/php/utils/Admin.php

class Admin {
    private $id;
    private $name;
    private $email;

    /**
     * Costruttore per la classe Admin.
     *
     * @param int    $id
     * @param string $name
     * @param string $email
     */
    public function __construct($id, $name, $email) {
        $this->id    = $id;
        $this->name  = $name;
        $this->email = $email;
    }

    /**
     * Restituisce un array associativo con i dati dell'admin.
     *
     * @return array
     */
    public function to_array() {
        return [
            'id'    => $this->id,
            'name'  => $this->name,
            'email' => $this->email
        ];
    }

    // Metodi getter
    public function getId() {
        return $this->id;
    }
    public function getName() {
        return $this->name;
    }
    public function getEmail() {
        return $this->email;
    }
}
?>
