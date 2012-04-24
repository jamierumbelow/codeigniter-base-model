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

// Turn off strict standards (until we deprecate MY_Model in favour of __construct)
error_reporting(E_ALL ^ E_STRICT);

// Load our MY_Model and the fakeish record model
require_once 'lib/MY_Model.php';
require_once 'tests/support/record_model.php';
require_once 'tests/support/database.php';

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