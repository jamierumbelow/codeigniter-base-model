<?php
/**
 * A base model with a series of CRUD functions (powered by CI's query builder),
 * validation-in-model support, event callbacks and more.
 *
 * @link http://github.com/jamierumbelow/codeigniter-base-model
 * @copyright Copyright (c) 2012, Jamie Rumbelow <http://jamierumbelow.net>
 */

class MY_Model extends CI_Model
{

    /* --------------------------------------------------------------
     * VARIABLES
     * ------------------------------------------------------------ */

    /**
     * This model's default database table. Automatically
     * guessed by pluralising the model name.
     */
    protected $_table;

    /** 
     * Database conn object; will use default connection 
     * unless overridden
     */
    protected $_db;

    /**
     * This model's default primary key or unique identifier.
     * Used by the get(), update() and delete() functions.
     */
    protected $primary_key = 'id';

    /**
     * Support for soft deletes and this model's 'deleted' key
     */
    protected $soft_delete = FALSE;
    protected $soft_delete_key = 'deleted';
    protected $_temporary_with_deleted = FALSE;

    /**
     * The various callbacks available to the model. Each are
     * simple lists of method names (methods will be run on $this).
     */
    protected $before_create = array();
    protected $after_create = array();
    protected $before_update = array();
    protected $after_update = array();
    protected $before_get = array();
    protected $after_get = array();
    protected $before_delete = array();
    protected $after_delete = array();

    protected $callback_parameters = array();

    /**
     * Protected, non-modifiable attributes
     */
    protected $protected_attributes = array();

    /**
     * Relationship arrays. Use flat strings for defaults or string
     * => array to customise the class name and primary key
     */
    protected $belongs_to = array();
    protected $has_many = array();

    protected $_with = array();

    /**
     * An array of validation rules. This needs to be the same format
     * as validation rules passed to the Form_validation library.
     */
    protected $validate = array();

    /**
     * Optionally skip the validation. Used in conjunction with
     * skip_validation() to skip data validation for any future calls.
     */
    protected $skip_validation = FALSE;

    /**
     * By default we return our results as objects. If we need to override
     * this, we can, or, we could use the `as_array()` and `as_object()` scopes.
     */
    protected $return_type = 'object';
    protected $_temporary_return_type = NULL;

    /* --------------------------------------------------------------
     * GENERIC METHODS
     * ------------------------------------------------------------ */

    /**
     * Initialise the model, tie into the CodeIgniter superobject and
     * try our best to guess the table name.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->helper('inflector');

        $this->_set_database();
        $this->_fetch_table();

        array_unshift($this->before_create, 'protect_attributes');
        array_unshift($this->before_update, 'protect_attributes');

        $this->_temporary_return_type = $this->return_type;
    }

    /* --------------------------------------------------------------
     * CRUD INTERFACE
     * ------------------------------------------------------------ */

    /**
     * Fetch a single record based on the primary key. Returns an object.
     */
    public function get($primary_value)
    {
        $this->trigger('before_get');

        if ($this->soft_delete && $this->_temporary_with_deleted !== TRUE)
        {
            $this->db->where($this->soft_delete_key, FALSE);
        }

        $row = $this->db->where($this->primary_key, $primary_value)
                        ->get($this->_table)
                        ->{$this->_return_type()}();
        $this->_temporary_return_type = $this->return_type;

        $row = $this->trigger('after_get', $row);

        return $row;
    }

    /**
     * Fetch a single record based on an arbitrary WHERE call. Can be
     * any valid value to $this->db->where().
     */
    public function get_by()
    {
        $where = func_get_args();
        $this->_set_where($where);

        if ($this->soft_delete && $this->_temporary_with_deleted !== TRUE)
        {
            $this->db->where($this->soft_delete_key, FALSE);
        }

        $this->trigger('before_get');

        $row = $this->db->get($this->_table)
                        ->{$this->_return_type()}();
        $this->_temporary_return_type = $this->return_type;

        $row = $this->trigger('after_get', $row);

        return $row;
    }

    /**
     * Fetch an array of records based on an array of primary values.
     */
    public function get_many($values)
    {
        if ($this->soft_delete && $this->_temporary_with_deleted !== TRUE)
        {
            $this->db->where($this->soft_delete_key, FALSE);
        }

        $this->db->where_in($this->primary_key, $values);

        return $this->get_all();
    }

    /**
     * Fetch an array of records based on an arbitrary WHERE call.
     */
    public function get_many_by()
    {
        $where = func_get_args();
        $this->_set_where($where);

        if ($this->soft_delete && $this->_temporary_with_deleted !== TRUE)
        {
            $this->db->where($this->soft_delete_key, FALSE);
        }

        return $this->get_all();
    }

    /**
     * Fetch all the records in the table. Can be used as a generic call
     * to $this->db->get() with scoped methods.
     */
    public function get_all()
    {
        $this->trigger('before_get');
        
        if ($this->soft_delete && $this->_temporary_with_deleted !== TRUE)
        {
            $this->db->where($this->soft_delete_key, FALSE);
        }

        $result = $this->db->get($this->_table)
                           ->{$this->_return_type(1)}();
        $this->_temporary_return_type = $this->return_type;

        foreach ($result as &$row)
        {
            $row = $this->trigger('after_get', $row);
        }

        return $result;
    }

    /**
     * Insert a new row into the table. $data should be an associative array
     * of data to be inserted. Returns newly created ID.
     */
    public function insert($data, $skip_validation = FALSE)
    {
        $valid = TRUE;

        if ($skip_validation === FALSE)
        {
            $valid = $this->_run_validation($data);
        }

        if ($valid)
        {
            $data = $this->trigger('before_create', $data);

            $this->db->insert($this->_table, $data);
            $insert_id = $this->db->insert_id();

            $this->trigger('after_create', $insert_id);
            
            return $insert_id;
        } 
        else
        {
            return FALSE;
        }
    }

    /**
     * Insert multiple rows into the table. Returns an array of multiple IDs.
     */
    public function insert_many($data, $skip_validation = FALSE)
    {
        $ids = array();

        foreach ($data as $row)
        {
            $ids[] = $this->insert($row, $skip_validation);
        }

        return $ids;
    }

    /**
     * Updated a record based on the primary value.
     */
    public function update($primary_value, $data, $skip_validation = FALSE)
    {
        $valid = TRUE;

        $data = $this->trigger('before_update', $data);

        if ($skip_validation === FALSE)
        {
            $valid = $this->_run_validation($data);
        }

        if ($valid)
        {
            $result = $this->db->where($this->primary_key, $primary_value)
                               ->set($data)
                               ->update($this->_table);
            
            $this->trigger('after_update', array($data, $result));

            return $result;
        }
        else
        {
            return FALSE;
        }
    }

    /**
     * Update many records, based on an array of primary values.
     */
    public function update_many($primary_values, $data, $skip_validation = FALSE)
    {
        $valid = TRUE;

        $data = $this->trigger('before_update', $data);

        if ($skip_validation === FALSE)
        {
            $valid = $this->_run_validation($data);
        }

        if ($valid)
        {
            $result = $this->db->where_in($this->primary_key, $primary_values)
                               ->set($data)
                               ->update($this->_table);
            
            $this->trigger('after_update', array($data, $result));

            return $result;
        }
        else
        {
            return FALSE;
        }
    }

    /**
     * Updated a record based on an arbitrary WHERE clause.
     */
    public function update_by()
    {
        $args = func_get_args();
        $data = array_pop($args);
        $this->_set_where($args);

        $data = $this->trigger('before_update', $data);

        if ($this->_run_validation($data))
        {
            $result = $this->db->set($data)
                               ->update($this->_table);
            $this->trigger('after_update', array($data, $result));

            return $result;
        }
        else
        {
            return FALSE;
        }
    }

    /**
     * Update all records
     */
    public function update_all($data)
    {
        $data = $this->trigger('before_update', $data);
        $result = $this->db->set($data)
                           ->update($this->_table);
        $this->trigger('after_update', array($data, $result));

        return $result;
    }

    /**
     * Delete a row from the table by the primary value
     */
    public function delete($id)
    {
        $this->trigger('before_delete', $id);

        $this->db->where($this->primary_key, $id);

        if ($this->soft_delete)
        {
            $result = $this->db->update($this->_table, array( $this->soft_delete_key => TRUE ));
        }
        else
        {
            $result = $this->db->delete($this->_table);
        }

        $this->trigger('after_delete', $result);

        return $result;
    }

    /**
     * Delete a row from the database table by an arbitrary WHERE clause
     */
    public function delete_by()
    {
        $where = func_get_args();
        $this->_set_where($where);

        $where = $this->trigger('before_delete', $where);

        if ($this->soft_delete)
        {
            $result = $this->db->update($this->_table, array( $this->soft_delete_key => TRUE ));
        }
        else
        {
            $result = $this->db->delete($this->_table);
        }

        $this->trigger('after_delete', $result);

        return $result;
    }

    /**
     * Delete many rows from the database table by multiple primary values
     */
    public function delete_many($primary_values)
    {
        $primary_values = $this->trigger('before_delete', $primary_values);
        
        $this->db->where_in($this->primary_key, $primary_values);

        if ($this->soft_delete)
        {
            $result = $this->db->update($this->_table, array( $this->soft_delete_key => TRUE ));
        }
        else
        {
            $result = $this->db->delete($this->_table);
        }

        $this->trigger('after_delete', $result);

        return $result;
    }


    /**
     * Truncates the table
     */
    public function truncate()
    {
        $result = $this->db->truncate($this->_table);

        return $result;
    }    

    /* --------------------------------------------------------------
     * RELATIONSHIPS
     * ------------------------------------------------------------ */

    public function with($relationship)
    {
        $this->_with[] = $relationship;

        if (!in_array('relate', $this->after_get))
        {
            $this->after_get[] = 'relate';
        }

        return $this;
    }

    public function relate($row)
    {
        foreach ($this->belongs_to as $key => $value)
        {
            if (is_string($value))
            {
                $relationship = $value;
                $options = array( 'primary_key' => $value . '_id', 'model' => $value . '_model' );
            }
            else
            {
                $relationship = $key;
                $options = $value;
            }

            if (in_array($relationship, $this->_with))
            {
                $this->load->model($options['model']);
                $row->{$relationship} = $this->{$options['model']}->get($row->{$options['primary_key']});
            }
        }

        foreach ($this->has_many as $key => $value)
        {
            if (is_string($value))
            {
                $relationship = $value;
                $options = array( 'primary_key' => singular($this->_table) . '_id', 'model' => singular($value) . '_model' );
            }
            else
            {
                $relationship = $key;
                $options = $value;
            }

            if (in_array($relationship, $this->_with))
            {
                $this->load->model($options['model']);
                $row->{$relationship} = $this->{$options['model']}->get_many_by($options['primary_key'], $row->{$this->primary_key});
            }
        }

        $this->_with = array();

        return $row;
    }

    /* --------------------------------------------------------------
     * UTILITY METHODS
     * ------------------------------------------------------------ */

    /**
     * Retrieve and generate a form_dropdown friendly array
     */
    function dropdown()
    {
        $args = func_get_args();

        if(count($args) == 2)
        {
            list($key, $value) = $args;
        }
        else
        {
            $key = $this->primary_key;
            $value = $args[0];
        }

        if ($this->soft_delete && $this->_temporary_with_deleted !== TRUE)
        {
            $this->db->where($this->soft_delete_key, FALSE);
        } 

        $this->trigger('before_dropdown', array( $key, $value ));

        $result = $this->db->select(array($key, $value))
                           ->get($this->_table)
                           ->result();

        $options = array();

        foreach ($result as $row)
        {
            $options[$row->{$key}] = $row->{$value};
        }

        $options = $this->trigger('after_dropdown', $options);
        
        return $options;
    }

    /**
     * Fetch a count of rows based on an arbitrary WHERE call.
     */
    public function count_by()
    {
        $where = func_get_args();
        $this->_set_where($where);

        return $this->db->count_all_results($this->_table);
    }

    /**
     * Fetch a total count of rows, disregarding any previous conditions
     */
    public function count_all()
    {
        return $this->db->count_all($this->_table);
    }

    /**
     * Tell the class to skip the insert validation
     */
    public function skip_validation()
    {
        $this->skip_validation = TRUE;
        return $this;
    }

    /**
     * Get the skip validation status
     */
    public function get_skip_validation()
    {
        return $this->skip_validation;
    }

    /**
     * Return the next auto increment of the table. Only tested on MySQL.
     */
    public function get_next_id()
    {
        return (int) $this->db->select('AUTO_INCREMENT')
            ->from('information_schema.TABLES')
            ->where('TABLE_NAME', $this->_table)
            ->where('TABLE_SCHEMA', $this->db->database)->get()->row()->AUTO_INCREMENT;
    }

    /**
     * Getter for the table name
     */
    public function table()
    {
        return $this->_table;
    }

    /* --------------------------------------------------------------
     * GLOBAL SCOPES
     * ------------------------------------------------------------ */

    /**
     * Return the next call as an array rather than an object
     */
    public function as_array()
    {
        $this->_temporary_return_type = 'array';
        return $this;
    }

    /**
     * Return the next call as an object rather than an array
     */
    public function as_object()
    {
        $this->_temporary_return_type = 'object';
        return $this;
    }

    /**
     * Don't care about soft deleted rows on the next call
     */
    public function with_deleted()
    {
        $this->_temporary_with_deleted = TRUE;
        return $this;
    }

    /* --------------------------------------------------------------
     * OBSERVERS
     * ------------------------------------------------------------ */

    /**
     * MySQL DATETIME created_at and updated_at
     */
    public function created_at($row)
    {
        if (is_object($row))
        {
            $row->created_at = date('Y-m-d H:i:s');
        }
        else
        {
            $row['created_at'] = date('Y-m-d H:i:s');
        }
        
        return $row;
    }

    public function updated_at($row)
    {
        if (is_object($row))
        {
            $row->updated_at = date('Y-m-d H:i:s');
        }
        else
        {
            $row['updated_at'] = date('Y-m-d H:i:s');
        }

        return $row;
    }

    /**
     * Serialises data for you automatically, allowing you to pass
     * through objects and let it handle the serialisation in the background
     */
    public function serialize($row)
    {
        foreach ($this->callback_parameters as $column)
        {
            $row[$column] = serialize($row[$column]);
        }

        return $row;
    }

    public function unserialize($row)
    {
        foreach ($this->callback_parameters as $column)
        {
            if (is_array($row))
            {
                $row[$column] = unserialize($row[$column]);
            }
            else
            {
                $row->$column = unserialize($row->$column);
            }
        }

        return $row;
    }

    /**
     * Protect attributes by removing them from $row array
     */
    public function protect_attributes($row)
    {
        foreach ($this->protected_attributes as $attr)
        {
            if (is_object($row))
            {
                unset($row->$attr);
            }
            else
            {
                unset($row[$attr]);
            }
        }

        return $row;
    }

    /* --------------------------------------------------------------
     * QUERY BUILDER DIRECT ACCESS METHODS
     * ------------------------------------------------------------ */

    /**
     * A wrapper to $this->db->order_by()
     */
    public function order_by($criteria, $order = 'ASC')
    {
        if ( is_array($criteria) )
        {
            foreach ($criteria as $key => $value)
            {
                $this->db->order_by($key, $value);
            }
        }
        else
        {
            $this->db->order_by($criteria, $order);
        }
        return $this;
    }

    /**
     * A wrapper to $this->db->limit()
     */
    public function limit($limit, $offset = 0)
    {
        $this->db->limit($limit, $offset);
        return $this;
    }

    /* --------------------------------------------------------------
     * INTERNAL METHODS
     * ------------------------------------------------------------ */

    /**
     * Trigger an event and call its observers
     */
    public function trigger($event, $data = FALSE)
    {
        if (isset($this->$event) && is_array($this->$event))
        {
            foreach ($this->$event as $method)
            {
                if (strpos($method, '('))
                {
                    preg_match('/([a-zA-Z0-9\_\-]+)(\(([a-zA-Z0-9\_\-\., ]+)\))?/', $method, $matches);

                    $method = $matches[1];
                    $this->callback_parameters = explode(',', $matches[3]);
                }

                $data = call_user_func_array(array($this, $method), array($data));
            }
        }
        
        return $data;
    }

    /**
     * Run validation on the passed data
     */
    private function _run_validation($data)
    {
        if($this->skip_validation)
        {
            return TRUE;
        }

        if(!empty($this->validate))
        {
            foreach($data as $key => $val)
            {
                $_POST[$key] = $val;
            }

            $this->load->library('form_validation');

            if(is_array($this->validate))
            {
                $this->form_validation->set_rules($this->validate);

                return $this->form_validation->run();
            }
            else
            {
                return $this->form_validation->run($this->validate);
            }
        }
        else
        {
            return TRUE;
        }
    }

    /**
     * Guess the table name by pluralising the model name
     */
    private function _fetch_table()
    {
        if ($this->_table == NULL)
        {
            $this->_table = plural(preg_replace('/(_m|_model)?$/', '', strtolower(get_class($this))));
        }
    }

    /* --------------------------------------------------------------
     * MULTIPLE DB LOADING
     * ------------------------------------------------------------ */

    private function _set_database()
    {
        if (!$this->_db)
        {
            $this->load->database();
        }    
        else
        {
            $this->db = $this->load->database($this->_db, TRUE);
        }
    }


    /**
     * Set WHERE parameters, cleverly
     */
    protected function _set_where($params)
    {
        if (count($params) == 1)
        {
            $this->db->where($params[0]);
        }
        else
        {
            $this->db->where($params[0], $params[1]);
        }
    }

    /**
     * Return the method name for the current return type
     */
    protected function _return_type($multi = FALSE)
    {
        $method = ($multi) ? 'result' : 'row';
        return $this->_temporary_return_type == 'array' ? $method . '_array' : $method;
    }
}
