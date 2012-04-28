<?php
/**
 * A base model with a series of CRUD functions (powered by CI's query builder),
 * validation-in-model support, event callbacks and more.
 *
 * @link http://github.com/jamierumbelow/codeigniter-base-model
 * @copyright Copyright (c) 2012, Jamie Rumbelow <http://jamierumbelow.net>
 */

/**
 * test_helper.php is the bootstrap file for our tests - it loads up an 
 * appropriate faux-CodeIgniter environment for our tests to run in.
 */

// Load our MY_Model and the fakeish record model
require_once 'core/MY_Model.php';

require_once 'tests/support/database.php';

require_once 'tests/support/models/record_model.php';
require_once 'tests/support/models/before_callback_model.php';
require_once 'tests/support/models/after_callback_model.php';

/**
 * Fake the CodeIgniter base model!
 */
class CI_Model
{
    public function __construct()
    {
        $this->load = new CI_Loader();
    }
}

/**
 * The loads happen in the constructor (before we can mock anything out),
 * so instead we'll fakeify the Loader
 */
class CI_Loader
{
    public function __call($method, $params = array()) {}
}

/**
 * We also need to fake the inflector
 */
function plural($name)
{
    return 'records';
}

/**
 * Let our tests know about our callbacks
 */

class MY_Model_Test_Exception extends Exception
{
    public $passed_object = FALSE;

    public function __construct($passed_object, $message = '')
    {
        parent::__construct($message);
        $this->passed_object = $passed_object;
    }
}

class Callback_Test_Exception extends MY_Model_Test_Exception
{
    public function __construct($passed_object)
    {
        parent::__construct($passed_object, 'Callback is being successfully thrown');
    }
}