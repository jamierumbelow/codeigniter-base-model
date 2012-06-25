<?php
/**
 * CodeIgniter Base CRUD Model with optional MongoDB support
 *
 * The library extends (and so replaces) CodeIgniter's native model
 * class to add common CRUD, validation features with optional MongoDB
 * support. It's uses V2 branch of "CodeIgniter MongoDB Active Record Library"
 * by Alex Bilbie as MongoDB interface:
 * https://github.com/alexbilbie/codeigniter-mongodb-library
 *
 * The code is based on Jamie Rumbelow's CRUD model:
 * http://github.com/jamierumbelow/codeigniter-base-model
 *
 * @package     CodeIgniter
 * @author      Sepehr Lajevardi <me@sepehr.ws>
 * @copyright   Copyright (c) 2012 Sepehr Lajevardi.
 * @license     http://codeigniter.com/user_guide/license.html
 * @link        https://github.com/sepehr/ci-mongodb-base-model
 * @version     Version 1.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * Base CRUD Model with optional MongoDB support.
 *
 * You might want to rename this class regarding better semantics.
 *
 * @package     CodeIgniter
 * @subpackage  Models
 * @category    Models
 * @author      Sepehr Lajevardi <me@sepehr.ws>
 * @link        https://github.com/sepehr/ci-mongodb-base-model
 * @todo        Re-document!
 */
class Base_Model extends MY_Model {

    /**
     * Indicates whether it's a MongoDB model or not.
     */
    protected $_mongodb;

    /**
     * Model's database interface object name.
     */
    protected $_interface;

    /**
     * Model's default database table/collection.
     * Automatically guessed by pluralising the model name.
     */
    protected $_datasource;

    /**
     * If using MongoDB, this may contain the collection fields with
     * their default values. If set the model will check new documents
     * against this array.
     */
    protected $_fields = array();

    /**
     * This model's default primary key or unique identifier.
     * Used by the get(), update() and delete() functions.
     *
     * Using MongoDB it's forced to "_id".
     */
    protected $primary_key = 'id';

    /**
     * The various callbacks available to the model. Each are
     * simple lists of method names (methods will be run on $this).
     */
    protected $before_create = array();
    protected $after_create  = array();
    protected $before_update = array();
    protected $after_update  = array();
    protected $before_get    = array();
    protected $after_get     = array();
    protected $before_delete = array();
    protected $after_delete  = array();

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

    // ------------------------------------------------------------------------

    /**
     * Initialize the model, tie into the CodeIgniter superobject and
     * try our best to guess the datasource name.
     */
    public function __construct()
    {
        parent::__construct();

        if ($this->_mongodb)
        {
            // Load MongoDB library
            $this->load->library('mongo_db');
            // Force _id as primary key if using MongoDB
            $this->primary_key = '_id';
            // Set interface object name
            $this->_interface = 'mongo_db';
        }
        else
        {
            // Make sure that database driver is present
            $this->load->database();
            // Set interface object name
            $this->_interface = 'db';
        }

        // Load inflector helper
        $this->load->helper('inflector');

        // Guess table/collection name
        $this->_guess_datasource();

        // Set return type of results
        $this->_temporary_return_type = $this->return_type;
    }

    // ------------------ CRUD Interface --------------------------------------

    /**
     * Fetch a single record/document based on the primary key. Returns an object.
     */
    public function get($primary_value)
    {
        // Run registered callbacks
        $this->_run_before_callbacks('get');

        $result = $this->{$this->_interface}
            ->where($this->primary_key, $this->_prep_primary($primary_value))
            ->get($this->_datasource);
        $this->_typecast($result);

        // Run registered callbacks
        $this->_run_after_callbacks('get', array($result));

        return $result;
    }

    // ------------------------------------------------------------------------

    /**
     * Fetch a single record/document based on an arbitrary WHERE call. Can be
     * any valid value to $this->{$this->_interface}->where().
     */
    public function get_by()
    {
        $where = func_get_args();
        $this->_set_where($where);

        // Run registered callbacks
        $this->_run_before_callbacks('get');

        $results = $this->{$this->_interface}->get($this->_datasource);
        $this->_typecast($results);

        // Run registered callbacks
        $this->_run_after_callbacks('get', array($results));

        return $results;
    }

    // ------------------------------------------------------------------------

    /**
     * Fetch an array of records/documents based on an array of primary values.
     */
    public function get_many($values)
    {
        $this->{$this->_interface}->where_in($this->primary_key, $this->_prep_primary($values));
        return $this->get_all();
    }

    // ------------------------------------------------------------------------

    /**
     * Fetch an array of records/documents based on an arbitrary WHERE call.
     */
    public function get_many_by()
    {
        $where = func_get_args();
        $this->_set_where($where);

        return $this->get_all();
    }

    // ------------------------------------------------------------------------

    /**
     * Fetch all the records/documents in the table/collection.
     * Can be used as a generic call to $this->{$this->_interface}->get() with scoped methods.
     */
    public function get_all()
    {
        // Run registered callbacks
        $this->_run_before_callbacks('get');

        $results = $this->{$this->_interface}->get($this->_datasource);
        $this->_typecast($results, TRUE);

        foreach ($results as &$result)
        {
            // Run registered callbacks per each result
            $result = $this->_run_after_callbacks('get', array($result));
        }

        return $results;
    }

    // ------------------------------------------------------------------------

    /**
     * Insert a new record/document into the table/collection.
     * $data should be an associative array of data to be inserted.
     * Returns newly created ID.
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
            // Run registered callbacks
            $data = $this->_run_before_callbacks('create', array( $data ));

            // Prepare data if using MongoDB
            $data = $this->_prep_fields($data);

            $insert_id = $this->{$this->_interface}->insert($this->_datasource, $data);

            // Update insert_id if not MongoDB
            !$this->_mongodb AND $insert_id = $this->{$this->_interface}->insert_id();

            // Run registered callbacks
            $this->_run_after_callbacks('create', array( $data, $insert_id ));

            return $insert_id;
        }
        else
        {
            return FALSE;
        }
    }

    // ------------------------------------------------------------------------

    /**
     * Insert multiple rows/documents into the table/collection.
     * Returns an array of multiple IDs.
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

    // ------------------------------------------------------------------------

    /**
     * Updated a record/document based on the primary value.
     */
    public function update($primary_value, $data, $skip_validation = FALSE)
    {
        $valid = TRUE;

        // Run registered callbacks
        $data = $this->_run_before_callbacks('update', array( $data, $primary_value ));

        if ($skip_validation === FALSE)
        {
            $valid = $this->_run_validation($data);
        }

        if ($valid)
        {
            // Prepare data if using MongoDB
            $data = $this->_prep_fields($data);

            $result = $this->{$this->_interface}
                ->where($this->primary_key, $this->_prep_primary($primary_value))
                ->set($data)
                ->update($this->_datasource);

            // Run registered callbacks
            $this->_run_after_callbacks('update', array( $data, $primary_value, $result ));

            return $result;
        }
        else
        {
            return FALSE;
        }
    }

    // ------------------------------------------------------------------------

    /**
     * Update many records/documents, based on an array of primary values.
     */
    public function update_many($primary_values, $data, $skip_validation = FALSE)
    {
        $valid = TRUE;

        // Run registered callbacks
        $data = $this->_run_before_callbacks('update', array( $data, $primary_values ));

        if ($skip_validation === FALSE)
        {
            $valid = $this->_run_validation($data);
        }

        if ($valid)
        {
            $result = $this->{$this->_interface}
                ->where_in($this->primary_key, $this->_prep_primary($primary_values))
                ->set($data)
                ->update($this->_datasource);

            // Run registered callbacks
            $this->_run_after_callbacks('update', array( $data, $primary_values, $result ));

            return $result;
        }
        else
        {
            return FALSE;
        }
    }

    // ------------------------------------------------------------------------

    /**
     * Updated a record/document based on an arbitrary WHERE clause.
     */
    public function update_by()
    {
        $args = func_get_args();
        $data = array_pop($args);
        $this->_set_where($args);

        // Run registered callbacks
        $data = $this->_run_before_callbacks('update', array( $data, $args ));

        if ($this->_run_validation($data))
        {
            $result = $this->{$this->_interface}
                ->set($data)
                ->update($this->_datasource);

            // Run registered callbacks
            $this->_run_after_callbacks('update', array( $data, $args, $result ));

            return $result;
        }
        else
        {
            return FALSE;
        }
    }

    // ------------------------------------------------------------------------

    /**
     * Update all records/documents.
     */
    public function update_all($data)
    {
        // Run registered callbacks
        $data = $this->_run_before_callbacks('update', array( $data ));

        $result = $this->{$this->_interface}
            ->set($data)
            ->update($this->_datasource);

        // Run registered callbacks
        $this->_run_after_callbacks('update', array( $data, $result ));

        return $result;
    }

    // ------------------------------------------------------------------------

    /**
     * Delete a row/document from the table/collection by the primary value
     */
    public function delete($id)
    {
        // Run registered callbacks
        $data = $this->_run_before_callbacks('delete', array( $id ));

        $result = $this->{$this->_interface}
            ->where($this->primary_key, $this->_prep_primary($id))
            ->delete($this->_datasource);

        // Run registered callbacks
        $this->_run_after_callbacks('delete', array( $id, $result ));

        return $result;
    }

    // ------------------------------------------------------------------------

    /**
     * Delete a row/document from the database by an arbitrary WHERE clause
     */
    public function delete_by()
    {
        $where = func_get_args();
        $this->_set_where($where);

        // Run registered callbacks
        $data = $this->_run_before_callbacks('delete', array( $where ));

        $result = $this->{$this->_interface}->delete($this->_datasource);

        // Run registered callbacks
        $this->_run_after_callbacks('delete', array( $where, $result ));

        return $result;
    }

    // ------------------------------------------------------------------------

    /**
     * Delete many rows/documents from the database table by multiple primary values
     */
    public function delete_many($primary_values)
    {
        // Run registered callbacks
        $data = $this->_run_before_callbacks('delete', array( $primary_values ));

        $result = $this->{$this->_interface}
            ->where_in($this->primary_key, $this->_prep_primary($primary_values))
            ->delete($this->_datasource);

        // Run registered callbacks
        $this->_run_after_callbacks('delete', array( $primary_values, $result ));

        return $result;
    }

    // ----------------------- Utility Methods --------------------------------

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

        // Run registered callbacks
        $this->_run_before_callbacks('get', array( $key, $value ));

        $result = $this->{$this->_interface}
            ->select(array($key, $value))
            ->get($this->_datasource);

        $this->_typecast($result, TRUE);

        // Run registered callbacks
        $this->_run_after_callbacks('get', array( $key, $value, $result ));

        $options = array();
        foreach ($result as $row)
        {
            $options[$row->{$key}] = $row->{$value};
        }

        return $options;
    }

    // ------------------------------------------------------------------------

    /**
     * Fetch a count of rows/documents based on an arbitrary WHERE call.
     */
    public function count_by()
    {
        $where = func_get_args();
        $this->_set_where($where);

        return $this->_count($where);
    }

    // ------------------------------------------------------------------------

    /**
     * Fetch a total count of rows/documents, disregarding any previous conditions
     */
    public function count_all()
    {
        return $this->_count(TRUE);
    }

    // ------------------------------------------------------------------------

    /**
     * Tell the class to skip the insert validation
     */
    public function skip_validation()
    {
        $this->skip_validation = TRUE;
        return $this;
    }

    // ------------------------------------------------------------------------

    /**
     * Get the skip validation status
     */
    public function get_skip_validation()
    {
        return $this->skip_validation;
    }

    // ------------------------------------------------------------------------

    /**
     * Return the next auto increment of the table. Only tested on MySQL.
     *
     * NOTE: Not working with MongoDB.
     */
    public function get_next_id()
    {

        return $this->_mongodb
            ? FALSE
            : (int) $this->{$this->_interface}
                ->select('AUTO_INCREMENT')
                ->from('information_schema.TABLES')
                ->where('TABLE_NAME', $this->_datasource)
                ->where('TABLE_SCHEMA', $this->{$this->_interface}->database)->get()->row()->AUTO_INCREMENT;
    }

    // ------------------------------------------------------------------------

    /**
     * Getter for the table/collection name
     */
    public function datasource()
    {
        return $this->_datasource;
    }

    // ------------------------------------------------------------------------

    /**
     * Return the next call as an array rather than an object
     */
    public function as_array()
    {
        $this->_temporary_return_type = 'array';
        return $this;
    }

    // ------------------------------------------------------------------------

    /**
     * Return the next call as an object rather than an array
     */
    public function as_object()
    {
        $this->_temporary_return_type = 'object';
        return $this;
    }

    // ------------------ Query builder direct access methods -----------------

    /**
     * A wrapper to $this->{$this->_interface}->order_by()
     */
    public function order_by($criteria, $order = 'ASC')
    {
        if ( is_array($criteria) )
        {
            foreach ($criteria as $key => $value)
            {
                $this->_mongo_db
                    ? $this->mongo_db->order_by(array($key => $value))
                    : $this->db->order_by($key, $value);
            }
        }
        else
        {
            $this->_mongo_db
                ? $this->mongo_db->order_by(array($criteria => $order))
                : $this->db->order_by($criteria, $order);
        }

        return $this;
    }

    // ------------------------------------------------------------------------

    /**
     * A wrapper to $this->{$this->_interface}->limit()
     */
    public function limit($limit, $offset = 0)
    {
        $this->_mongo_db
            ? $this->mongo_db->limit($limit)->offset($offset)
            : $this->db->limit($limit, $offset);

        return $this;
    }

    // ------------------ Internal Helpers ------------------------------------

    /**
     * Run the before_ callbacks, each callback taking a $data
     * variable and returning it
     */
    private function _run_before_callbacks($type, $params = array())
    {
        $name = 'before_' . $type;
        $data = (isset($params[0])) ? $params[0] : FALSE;

        if (!empty($this->$name))
        {
            foreach ($this->$name as $method)
            {
                $data += call_user_func_array(array($this, $method), $params);
            }
        }

        return $data;
    }

    // ------------------------------------------------------------------------

    /**
     * Run the after_ callbacks, each callback taking a $data
     * variable and returning it
     */
    private function _run_after_callbacks($type, $params = array())
    {
        $name = 'after_' . $type;
        $data = (isset($params[0])) ? $params[0] : FALSE;

        if (!empty($this->$name))
        {
            foreach ($this->$name as $method)
            {
                $data = call_user_func_array(array($this, $method), $params);
            }
        }

        return $data;
    }

    // ------------------------------------------------------------------------

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

    // ------------------------------------------------------------------------

    /**
     * Set WHERE parameters, cleverly
     */
    private function _set_where($params)
    {
        count($params) == 1
            ? $this->{$this->_interface}->where($params[0])
            : $this->{$this->_interface}->where($params[0], $params[1]);
    }

    // ------------------------------------------------------------------------

    /**
     * Fetch count of rows/documents. If $all is set to TRUE is returns
     * the count of all rows/documents, otherwise it should contains where
     * conditions.
     */
    private function _count($all = TRUE)
    {
        if ($all === TRUE)
        {
            // Temporarily store buffered conditions
            $where_cache = $this->mongo_db->wheres;
            // If requested to count all documents, flush all buffered
            // conditions. There's no better way to do this at the moment!
            $this->mongo_db->wheres = array();
            // Set database driver proper method
            $method = 'count_all';
        }
        // $all contains where conditions:
        else{
            // Set conditions
            $this->_set_where($all);
            // Set database driver proper method
            $method = 'count_all_results';
        }

        $count = $this->_mongodb
            ? count($this->mongo_db->get($this->_datasource))
            : $this->db->$method($this->_datasource);

        // Restore MongoDB buffered conditions
        $this->mongo_db->wheres = $where_cache;

        return $count;
    }

    // ------------------------------------------------------------------------

    /**
     * Return the method name for the current return type (non-mongodb):
     * - result
     * - result_array
     * - row
     * - row_array
     */
    private function _return_type($multi = FALSE)
    {
        $method = ($multi) ? 'result' : 'row';
        return $this->_temporary_return_type == 'array' ? $method . '_array' : $method;
    }

    // ------------------------------------------------------------------------

    /**
     * Guess the table/collection name by pluralising its model name.
     */
    private function _guess_datasource()
    {
        if ($this->_datasource == NULL)
        {
            $this->_datasource = plural(preg_replace('/(_m|_model)?$/', '', strtolower(get_class($this))));;
        }
    }

    // ------------------------------------------------------------------------

    /**
     * Typecasts results as desired.
     */
    private function _typecast(&$results, $multiple = FALSE)
    {
        if ($multiple)
        {
            // Typecast each element in the results array
            foreach ($results as &$result)
            {
                $this->_typecast($result);
            }
        }
        else
        {
            if ($this->_mongodb)
            {
                isset($results[0]) AND $results = $results[0];
                $results = $this->_temporary_return_type == 'object'
                    ? (object) $results
                    : (array)  $results;
            }
            else
            {
                $results = $results->{$this->_return_type($multiple)}();
            }
        }

        $this->_temporary_return_type = $this->return_type;
    }

    // ------------------------------------------------------------------------

    /**
     * Prepares passed mongoids for database queries.
     */
    private function _prep_primary($primary_value)
    {
        // Not using MongoDB?
        if ( !$this->_mongodb)
        {
            return $primary_value;
        }

        // Array of primary values?
        if (is_array($primary_value))
        {
            foreach ($primary_value as $key => $value)
            {
                $primary_value[$key] = $this->_prep_primary($value);
            }
        }

        // Single primary value
        else
        {
            $primary_value = new MongoId($primary_value);
        }

        return $primary_value;
    }

    // ------------------------------------------------------------------------

    /**
     * Prepares a document for MongoDB insert/update operation
     * using the pre-filled $_fields schema dictionary.
     *
     * This will save us from Null-byte and SQL-like injection attacks,
     * Also ensures that we got default values of unset fields in the
     * passing document.
     *
     * Since MongoDB is a schema-less database, we better do this on the
     * application side!
     */
    public function _prep_fields($fields)
    {
        if ( ! $this->_mongodb OR empty($this->_fields))
        {
            return $fields;
        }

        // Remove extra fields from the passing document
        foreach ($fields as $key => $value)
        {
            // Null-byte injection?
            if (!isset($this->_fields[$key]))
            {
                unset($fields[$key]);
            }

            // SQL-like injection?
            else
            {
                $fields[$key] = (string) $value;
            }
        }

        // Ensure default values
        $fields = array_merge($this->_fields, $fields);

        return $fields;
    }

}
// End of Base_Model class


/**
 * For sanity sake of CI conventions.
 */
class MY_Model extends CI_Model {

    public function __construct()
    {
        parent::__construct();
    }

}
// End of MY_Model class


/* End of file MY_Model.php */
/* Location: ./application/core/MY_Model.php */