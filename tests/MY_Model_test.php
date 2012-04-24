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
        $this->_expect_get();
        $this->model->db->expects($this->once())
                        ->method('row')
                        ->will($this->returnValue('fake_record_here'));

        $this->assertEquals($this->model->get(2), 'fake_record_here');
    }

    public function test_get_by()
    {
        $this->model->db->expects($this->once())
                        ->method('where')
                        ->with($this->equalTo('some_column'), $this->equalTo('some_value'))
                        ->will($this->returnValue($this->model->db));
        $this->_expect_get();
        $this->model->db->expects($this->once())
                        ->method('row')
                        ->will($this->returnValue('fake_record_here'));

        $this->assertEquals($this->model->get_by('some_column', 'some_value'), 'fake_record_here');
    }

    public function test_get_many()
    {
        $this->model->db->expects($this->once())
                        ->method('where_in')
                        ->with($this->equalTo('id'), $this->equalTo(array(1, 2, 3, 4, 5)))
                        ->will($this->returnValue($this->model->db));
        $this->_expect_get();
        $this->model->db->expects($this->once())
                        ->method('result')
                        ->will($this->returnValue(array('fake', 'records', 'here')));

        $this->assertEquals($this->model->get_many(array(1, 2, 3, 4, 5)), array('fake', 'records', 'here'));
    }

    public function test_get_many_by()
    {
        $this->model->db->expects($this->once())
                        ->method('where')
                        ->with($this->equalTo('some_column'), $this->equalTo('some_value'))
                        ->will($this->returnValue($this->model->db));
        $this->_expect_get();
        $this->model->db->expects($this->once())
                        ->method('result')
                        ->will($this->returnValue(array('fake', 'records', 'here')));

        $this->assertEquals($this->model->get_many_by('some_column', 'some_value'), array('fake', 'records', 'here'));
    }

    public function test_get_all()
    {
        $this->_expect_get();
        $this->model->db->expects($this->once())
                        ->method('result')
                        ->will($this->returnValue(array('fake', 'records', 'here')));

        $this->assertEquals($this->model->get_all(), array('fake', 'records', 'here'));
    }

    /* --------------------------------------------------------------
     * UTILITIES
     * ------------------------------------------------------------ */

    protected function _expect_get()
    {
        $this->model->db->expects($this->once())
                        ->method('get')
                        ->with($this->equalTo('records'))
                        ->will($this->returnValue($this->model->db));
    }
}