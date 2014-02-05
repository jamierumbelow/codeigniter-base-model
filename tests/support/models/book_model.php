<?php

class Book_model extends MY_Model {

    protected $_table = 'books';

    public function __construct($database, $key = NULL)
    {
        $this->primary_key = $key;
        $this->_database = $database;
        $this->_fetch_primary_key();

        return $this;
    }

}