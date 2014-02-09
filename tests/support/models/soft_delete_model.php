<?php

class Soft_delete_model extends MY_Model
{
	protected $soft_delete = TRUE;

	public function __construct($key = 'deleted')
	{
		parent::__construct();

		$this->soft_delete_key = $key;
	}
}