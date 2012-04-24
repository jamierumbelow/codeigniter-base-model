<?php
/**
 * A base model with a series of CRUD functions (powered by CI's query builder),
 * validation-in-model support, event callbacks and more.
 *
 * @link http://github.com/jamierumbelow/codeigniter-base-model
 * @copyright Copyright (c) 2012, Jamie Rumbelow <http://jamierumbelow.net>
 */

require_once 'tests/support/test_helper.php';

class MY_Model_tests extends PHPUnit_Framework_TestCase
{
    protected $model;

    /* --------------------------------------------------------------
     * TEST INFRASTRUCTURE
     * ------------------------------------------------------------ */

    public function setUp()
    {
        $this->model = new Record_model();
        $this->model->db = $this->getMock('MY_Model_Mock_DB');
    }

    public function tearDown()
    {
        unset($this->model);
    }

    /* --------------------------------------------------------------
     * GENERIC METHODS
     * ------------------------------------------------------------ */

    public function test_constructor_guesses_the_table_name()
    {
        $this->model = new Record_model();

        $this->assertEquals($this->model->table(), 'records');
    }

    /* --------------------------------------------------------------
     * CRUD INTERFACE
     * ------------------------------------------------------------ */

    public function test_get()
    {
        $this->model->db->expects($this->once())
                        ->method('where')
                        ->with($this->equalTo('id'), $this->equalTo(2))
                        ->will($this->returnValue($this->model->db));
        $this->model->db->expects($this->once())
                        ->method('get')
                        ->with($this->equalTo('records'))
                        ->will($this->returnValue($this->model->db));
        $this->model->db->expects($this->once())
                        ->method('row')
                        ->will($this->returnValue('fake_record_here'));

        $this->assertEquals($this->model->get(2), 'fake_record_here');
    }
}