<?php
/**
 * A base model with a series of CRUD functions (powered by CI's query builder),
 * validation-in-model support, event callbacks and more.
 *
 * @link http://github.com/jamierumbelow/codeigniter-base-model
 * @copyright Copyright (c) 2012, Jamie Rumbelow <http://jamierumbelow.net>
 */

/**
 * before_callback_model.php contains a test model that defines every before callback as a function
 * that throws an exception. We can then catch that in the tests to ensure callbacks work.
 */

class Before_callback_model extends MY_Model
{
    protected $before_create = array('test_callback');
    protected $before_update = array('test_callback');
    protected $before_get = array('test_callback');
    protected $before_delete = array('test_callback');

    protected function test_callback($row = FALSE)
    {
        throw new Callback_Test_Exception($row);

        return $row;
    }
}