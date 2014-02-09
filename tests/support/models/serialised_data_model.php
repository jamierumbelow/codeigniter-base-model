<?php
/**
 * A base model with a series of CRUD functions (powered by CI's query builder),
 * validation-in-model support, event callbacks and more.
 *
 * @link http://github.com/jamierumbelow/codeigniter-base-model
 * @copyright Copyright (c) 2012, Jamie Rumbelow <http://jamierumbelow.net>
 */

/**
 * serialised_data_model.php contains a test model that includes serialising a columns
 */

class Serialised_data_model extends MY_Model
{
	public $before_create = array( 'serialize(data)' );
	public $before_update = array( 'serialize(data)' );
}