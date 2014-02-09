<?php
/**
 * A base model with a series of CRUD functions (powered by CI's query builder),
 * validation-in-model support, event callbacks and more.
 *
 * @link http://github.com/jamierumbelow/codeigniter-base-model
 * @copyright Copyright (c) 2012, Jamie Rumbelow <http://jamierumbelow.net>
 */

class Validated_model extends MY_Model
{
	public $validate = array(
		array( 'field' => 'name', 'label' => 'Name', 'rules' => 'required' ),
		array( 'field' => 'sexyness', 'label' => 'Sexyness', 'rules' => 'required' )
	);
}