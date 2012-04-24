<?php
/**
 * A base model with a series of CRUD functions (powered by CI's query builder),
 * validation-in-model support, event callbacks and more.
 *
 * @link http://github.com/jamierumbelow/codeigniter-base-model
 * @copyright Copyright (c) 2012, Jamie Rumbelow <http://jamierumbelow.net>
 */

/**
 * database.php is a fakeified CodeIgniter query builder
 */

class MY_Model_Mock_DB
{
    /**
     * CI_DB
     */
    public function where() { }
    public function where_in() { }
    public function get() { }
    public function insert() { }
    public function insert_id() { }

    /**
     * CI_DB_Result
     */
    public function row() { }
    public function result() { }
}