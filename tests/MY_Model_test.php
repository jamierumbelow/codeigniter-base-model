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
    
    public function test_get_callbacks_are_called_appropriately()
    {
        $this->model = new Before_callback_model();
        $this->model->db = $this->getMock('MY_Model_Mock_DB');

        $this->assertCallbackIsCalled(function(){ $this->model->get(1); });
        $this->assertCallbackIsCalled(function(){ $this->model->get_by('some_column', 'some_value'); });
        $this->assertCallbackIsCalled(function(){ $this->model->get_many(array(1, 2, 3, 4, 5)); });
        $this->assertCallbackIsCalled(function(){ $this->model->get_many_by('some_column', 'some_value'); });
        $this->assertCallbackIsCalled(function(){ $this->model->get_all(); });

        $this->model = new After_callback_model();
        $this->model->db = $this->getMock('MY_Model_Mock_DB');

        $this->model->db->expects($this->any())->method('where')->will($this->returnValue($this->model->db));
        $this->model->db->expects($this->any())->method('where_in')->will($this->returnValue($this->model->db));
        $this->model->db->expects($this->any())->method('get')->will($this->returnValue($this->model->db));

        $this->model->db->expects($this->any())->method('row')->will($this->returnValue('row_object'));
        $this->model->db->expects($this->any())->method('result')->will($this->returnValue(array('row_object_array')));

        $this->assertCallbackIsCalled(function(){ $this->model->get(1); }, 'row_object');
        $this->assertCallbackIsCalled(function(){ $this->model->get_by('some_column', 'some_value'); }, 'row_object');
        $this->assertCallbackIsCalled(function(){ $this->model->get_many(array(1, 2, 3, 4, 5)); }, 'row_object_array');
        $this->assertCallbackIsCalled(function(){ $this->model->get_many_by('some_column', 'some_value'); }, 'row_object_array');
        $this->assertCallbackIsCalled(function(){ $this->model->get_all(); }, 'row_object_array');
    }

    public function test_insert()
    {
        $this->model->db->expects($this->once())
                        ->method('insert')
                        ->with($this->equalTo('records'), $this->equalTo(array('new' => 'data')));
        $this->model->db->expects($this->any())
                        ->method('insert_id')
                        ->will($this->returnValue(123));

        $this->assertEquals($this->model->insert(array('new' => 'data')), 123);
    }

    public function test_insert_many()
    {
        $this->model->db->expects($this->exactly(2))
                        ->method('insert')
                        ->with($this->equalTo('records'));
        $this->model->db->expects($this->any())
                        ->method('insert_id')
                        ->will($this->returnValue(123));

        $this->assertEquals($this->model->insert_many(array(array('new' => 'data'), array('other' => 'data'))), array(123, 123));
    }

    public function test_update()
    {
        $this->model->db->expects($this->once())
                        ->method('where')
                        ->with($this->equalTo('id'), $this->equalTo(2))
                        ->will($this->returnValue($this->model->db));
        $this->model->db->expects($this->once())
                        ->method('set')
                        ->with($this->equalTo(array('new' => 'data')))
                        ->will($this->returnValue($this->model->db));
        $this->model->db->expects($this->once())
                        ->method('update')
                        ->with($this->equalTo('records'))
                        ->will($this->returnValue(TRUE));

        $this->assertEquals($this->model->update(2, array('new' => 'data')), TRUE);
    }

    // public function test_update_many()
    // {
    //     $this->model->db->expects($this->once())
    //                     ->method('where_in')
    //                     ->with($this->equalTo('id'), $this->equalTo(array(1, 2, 3, 4, 5)))
    //                     ->will($this->returnValue($this->model->db));
    //     $this->model->db->expects($this->once())
    //                     ->method('set')
    //                     ->with($this->equalTo(array('new' => 'data')))
    //                     ->will($this->returnValue($this->model->db));
    //     $this->model->db->expects($this->once())
    //                     ->method('update')
    //                     ->with($this->equalTo('records'))
    //                     ->will($this->returnValue(TRUE));

    //     $this->assertEquals($this->model->update_many(array(1, 2, 3, 4, 5), array('new' => 'data')), TRUE);
    // }

    public function test_update_by()
    {
        $this->model->db->expects($this->once())
                        ->method('where')
                        ->with($this->equalTo('some_column'), $this->equalTo('some_value'))
                        ->will($this->returnValue($this->model->db));
        $this->model->db->expects($this->once())
                        ->method('set')
                        ->with($this->equalTo(array('new' => 'data')))
                        ->will($this->returnValue($this->model->db));
        $this->model->db->expects($this->once())
                        ->method('update')
                        ->with($this->equalTo('records'))
                        ->will($this->returnValue(TRUE));

        $this->assertEquals($this->model->update_by('some_column', 'some_value', array('new' => 'data')), TRUE);
    }

    public function test_update_all()
    {
        $this->model->db->expects($this->once())
                        ->method('set')
                        ->with($this->equalTo(array('new' => 'data')))
                        ->will($this->returnValue($this->model->db));
        $this->model->db->expects($this->once())
                        ->method('update')
                        ->with($this->equalTo('records'))
                        ->will($this->returnValue(TRUE));

        $this->assertEquals($this->model->update_all(array('new' => 'data')), TRUE);
    }

    public function test_delete()
    {
        $this->model->db->expects($this->once())
                        ->method('where')
                        ->with($this->equalTo('id'), $this->equalTo(2))
                        ->will($this->returnValue($this->model->db));
        $this->model->db->expects($this->once())
                        ->method('delete')
                        ->with($this->equalTo('records'))
                        ->will($this->returnValue(TRUE));

        $this->assertEquals($this->model->delete(2), TRUE);
    }

    public function test_delete_by()
    {
        $this->model->db->expects($this->once())
                        ->method('where')
                        ->with($this->equalTo('some_column'), $this->equalTo('some_value'))
                        ->will($this->returnValue($this->model->db));
        $this->model->db->expects($this->once())
                        ->method('delete')
                        ->with($this->equalTo('records'))
                        ->will($this->returnValue(TRUE));

        $this->assertEquals($this->model->delete_by('some_column', 'some_value'), TRUE);
    }

    public function test_delete_many()
    {
        $this->model->db->expects($this->once())
                        ->method('where_in')
                        ->with($this->equalTo('id'), array(1, 2, 3, 4, 5))
                        ->will($this->returnValue($this->model->db));
        $this->model->db->expects($this->once())
                        ->method('delete')
                        ->with($this->equalTo('records'))
                        ->will($this->returnValue(TRUE));

        $this->assertEquals($this->model->delete_many(array(1, 2, 3, 4, 5)), TRUE);
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

    /* --------------------------------------------------------------
     * CUSTOM ASSERTIONS
     * ------------------------------------------------------------ */

    public function assertCallbackIsCalled($method, $params = FALSE)
    {
        try
        {
            $method();
        }
        catch (Callback_Test_Exception $e)
        {
            $this->assertEquals($e->passed_object, $params);
        }
    }
}