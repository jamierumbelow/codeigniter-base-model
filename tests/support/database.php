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
    public function select() { }
    public function where() { }
    public function where_in() { }
    public function get() { }
    public function from() { }
    public function insert() { }
    public function insert_id() { }
    public function set() { }
    public function update() { }
    public function delete() { }
    public function order_by() { }
    public function limit() { }
    public function count_all_results() { }
    public function count_all() { }

    /**
     * CI_DB_Result
     */
    public function row() { }
    public function result() { }
    public function row_array() { }
    public function result_array() { }
}