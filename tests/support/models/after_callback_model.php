<?php
/**
 * A base model with a series of CRUD functions (powered by CI's query builder),
 * validation-in-model support, event callbacks and more.
 *
 * @link http://github.com/jamierumbelow/codeigniter-base-model
 * @copyright Copyright (c) 2012, Jamie Rumbelow <http://jamierumbelow.net>
 */

/**
 * after_callback_model.php contains a test model that defines every after callback as a function
 * that throws an exception. We can then catch that in the tests to ensure callbacks work.
 */

class After_callback_model extends MY_Model
{
    protected $after_create = array('test_throw');
    protected $after_update = array('test_throw');
    protected $after_get = array('test_data_callback', 'test_data_callback_two');
    protected $after_delete = array('test_throw');

    protected function test_throw($row)
    {
    	throw new Callback_Test_Exception($row);
    }

    protected function test_data_callback($row)
    {
    	$row['key'] = 'Value';
    	return $row;
    }

    protected function test_data_callback_two($row)
    {
        $row['another_key'] = '123 Value';
        return $row;
    }
}