<?php
/**
 * A base model with a series of CRUD functions (powered by CI's query builder),
 * validation-in-model support, event callbacks and more.
 *
 * @link http://github.com/jamierumbelow/codeigniter-base-model
 * @copyright Copyright (c) 2012, Jamie Rumbelow <http://jamierumbelow.net>
 */

/**
 * protected_attributes_model.php contains a test model with protected attributes
 */

class Protected_attributes_model extends MY_Model
{
	public $protected_attributes = array( 'id', 'hash' );
}