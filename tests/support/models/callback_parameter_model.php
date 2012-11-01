<?php
/**
 * A base model with a series of CRUD functions (powered by CI's query builder),
 * validation-in-model support, event callbacks and more.
 *
 * @link http://github.com/jamierumbelow/codeigniter-base-model
 * @copyright Copyright (c) 2012, Jamie Rumbelow <http://jamierumbelow.net>
 */

/**
 * callback_parameter_model.php contains a test model that defines a callback
 * with embedded parameters
 */

class Callback_parameter_model extends MY_Model
{
	public $callback = array('some_callback(some_param,another_param)');

	public function some_method()
	{
		$this->trigger('callback');
	}

	protected function some_callback()
	{
		throw new Callback_Test_Exception($this->callback_parameters);
	}
}