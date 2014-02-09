<?php
/**
 * A base model with a series of CRUD functions (powered by CI's query builder),
 * validation-in-model support, event callbacks and more.
 *
 * @link http://github.com/jamierumbelow/codeigniter-base-model
 * @copyright Copyright (c) 2012, Jamie Rumbelow <http://jamierumbelow.net>
 */

/**
 * relationship_model.php contains a test model that has a belongs_to and has_many relationship
 */

class Belongs_to_model extends MY_Model
{
    public $belongs_to = array( 'author' );
    public $has_many = array( 'comments' );
}

class Author_model extends MY_Model
{
    protected $_table = 'authors';
}