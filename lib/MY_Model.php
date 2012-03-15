<?php
/**
 * A base model to provide the basic CRUD 
 * actions for all models that inherit from it.
 *
 * @package CodeIgniter
 * @subpackage MY_Model
 * @link http://github.com/jamierumbelow/codeigniter-base-model
 * @author Jamie Rumbelow <http://jamierumbelow.net>
 * @modified Phil Sturgeon <http://philsturgeon.co.uk>
 * @modified Dan Horrigan <http://dhorrigan.com>
 * @modified Adam Jackett <http://darkhousemedia.com>
 * @copyright Copyright (c) 2011, Jamie Rumbelow <http://jamierumbelow.net>
 */

class MY_Model extends CI_Model
{
    
    /**
     * The database table to use, only
     * set if you want to bypass the magic
     *
     * @var string
     */
    protected $_table;
        
    /**
     * The primary key, by default set to
     * `id`, for use in some functions.
     *
     * @var string
     */
    protected $primary_key = 'id';
    
    /**
     * Callbacks.
     *
     * @var array
     */
    protected $before_create = array();
    protected $after_create = array();
    protected $before_update = array();
    protected $after_update = array();
    protected $before_get = array();
    protected $after_get = array();
    protected $before_delete = array();
    protected $after_delete = array();
    
    /**
     * An array of validation rules
     *
     * @var array
     */
    protected $validate = array();

    /**
     * Skip the validation
     *
     * @var bool
     */
    protected $skip_validation = FALSE;

    /**
     * Wrapper to __construct for when loading
     * class is a superclass to a regular controller,
     * i.e. - extends Base not extends Controller.
     * 
     * @return void
     */
    public function MY_Model() { $this->__construct(); }

    /**
     * The class constructer, tries to guess
     * the table name.
     */
    public function __construct()
    {
        parent::__construct();
        
        $this->load->helper('inflector');
        
        $this->_fetch_table();
    }
    
    /**
     * Get a single record by creating a WHERE clause with
     * a value for your primary key
     *
     * @param string $primary_value The value of your primary key
     * @return object
     */
    public function get($primary_value)
    {
        $this->_run_before_callbacks('get');
        
        $row = $this->db->where($this->primary_key, $primary_value)
                        ->get($this->_table)
                        ->row();
                        
        $this->_run_after_callbacks('get', array( $row ));
        
        return $row;
    }
    
    /**
     * Get a single record by creating a WHERE clause by passing
     * through a CI AR where() call
     *
     * @param string $key The key to search by 
     * @param string $val The value of that key
     * @return object
     */
    public function get_by()
    {
        $where =& func_get_args();
        $this->_set_where($where);
        
        $this->_run_before_callbacks('get');
        $row = $this->db->get($this->_table)
                        ->row();
        $this->_run_after_callbacks('get', array( $row ));
        
        return $row;
    }
    
    /**
     * Similar to get(), but returns a result array of
     * many result objects.
     *
     * @param string $key The key to search by
     * @param string $values The value of that key
     * @return array
     */
    public function get_many($values)
    {
        $this->db->where_in($this->primary_key, $values);
        
        return $this->get_all();
    }
    
    /**
     * Similar to get_by(), but returns a result array of
     * many result objects.
     *
     * @param string $key The key to search by
     * @param string $val The value of that key
     * @return array
     */
    public function get_many_by()
    {
        $where =& func_get_args();
        $this->_set_where($where);
        
        return $this->get_all();
    }
    
    /**
     * Get all records in the database
     *
     * @return array
     */
    public function get_all()
    {
        $this->_run_before_callbacks('get');
        
        $result = $this->db->get($this->_table)
                            ->result();
                
        foreach ($result as &$row)
        {
            $row = $this->_run_after_callbacks('get', array( $row ));
        }
        
        return $result;
    }
    
    /**
     * Count the number of rows based on a WHERE
     * criteria
     *
     * @param string $key The key to search by
     * @param string $val The value of that key
     * @return integer
     */
    public function count_by()
    {
        $where =& func_get_args();
        $this->_set_where($where);
        
        return $this->db->count_all_results($this->_table);
    }
    
    /**
     * Return a count of every row in the table
     *
     * @return integer
     */
    public function count_all()
    {
        return $this->db->count_all($this->_table);
    }
    
    /**
     * Insert a new record into the database,
     * calling the before and after create callbacks.
     * Returns the insert ID.
     *
     * @param array $data Information
     * @return integer
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
            $data = $this->_run_before_callbacks('create', array( $data ));
            $this->db->insert($this->_table, $data);
            $insert_id = $this->db->insert_id();

            $this->_run_after_callbacks('create', array( $data, $insert_id ));
            
            return $insert_id;
        } 
        else 
        {
            return FALSE;
        }
    }
    
    /**
     * Similar to insert(), just passing an array to insert
     * multiple rows at once. Returns an array of insert IDs.
     *
     * @param array $data Array of arrays to insert
     * @return array
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
     * Update a record, specified by an ID.
     *
     * @param integer $id The row's ID
     * @param array $array The data to update
     * @return bool
     */
    public function update($primary_value, $data, $skip_validation = FALSE)
    {
        $valid = TRUE;
        
        $data = $this->_run_before_callbacks('update', array( $data, $primary_value ));
        
        if ($skip_validation === FALSE)
        {
            $valid = $this->_run_validation($data);
        }
        
        if ($valid)
        {
            $result = $this->db->where($this->primary_key, $primary_value)
                               ->set($data)
                               ->update($this->_table);
            $this->_run_after_callbacks('update', array( $data, $primary_value, $result ));
            
            return $result;
        } 
        else
        {
            return FALSE;
        }
    }
    
    /**
     * Update a record, specified by $key and $val.
     *
     * @param string $key The key to update with
     * @param string $val The value
     * @param array $array The data to update
     * @return bool
     */
    public function update_by()
    {
        $args =& func_get_args();
        $data = array_pop($args);
        $this->_set_where($args);
        
        $data = $this->_run_before_callbacks('update', array( $data, $args ));
        
        if ($this->_run_validation($data))
        {
            $result = $this->db->set($data)
                               ->update($this->_table);
            $this->_run_after_callbacks('update', array( $data, $args, $result ));
            
            return $result;
        }
        else
        {
            return FALSE;
        }
    }
    
    /**
     * Updates many records, specified by an array
     * of IDs.
     *
     * @param array $primary_values The array of IDs
     * @param array $data The data to update
     * @return bool
     */
    public function update_many($primary_values, $data, $skip_validation)
    {
        $valid = TRUE;
        
        $data = $this->_run_before_callbacks('update', array( $data, $primary_value ));
        
        if ($skip_validation === FALSE)
        {
            $valid = $this->_run_validation($data);
        }
        
        if ($valid)
        {
            $result = $this->db->where_in($this->primary_key, $primary_values)
                               ->set($data)
                               ->update($this->_table);
            $this->_run_after_callbacks('update', array( $data, $primary_value, $result ));
            
            return $result;
        } 
        else
        {
            return FALSE;
        }
    }
    
    /**
     * Updates all records
     *
     * @param array $data The data to update
     * @return bool
     */
    public function update_all($data)
    {
        $data = $this->_run_before_callbacks('update', array( $data ));
        $result = $this->db->set($data)
                           ->update($this->_table);
        $this->_run_after_callbacks('update', array( $data, $result ));
        
        return $result;
    }
    
    /**
     * Delete a row from the database table by the
     * ID.
     *
     * @param integer $id 
     * @return bool
     */
    public function delete($id)
    {
        $data = $this->_run_before_callbacks('delete', array( $id ));
        $result = $this->db->where($this->primary_key, $id)
                           ->delete($this->_table);
        $this->_run_after_callbacks('delete', array( $id, $result ));
        
        return $result;
    }
    
    /**
     * Delete a row from the database table by the
     * key and value.
     *
     * @param string $key
     * @param string $value 
     * @return bool
     */
    public function delete_by()
    {
        $where =& func_get_args();
        $this->_set_where($where);
        
        $data = $this->_run_before_callbacks('delete', array( $where ));
        $result = $this->db->delete($this->_table);
        $this->_run_after_callbacks('delete', array( $where, $result ));
        
        return $result;
    }
    
    /**
     * Delete many rows from the database table by 
     * an array of IDs passed.
     *
     * @param array $primary_values 
     * @return bool
     */
    public function delete_many($primary_values)
    {
        $data = $this->_run_before_callbacks('delete', array( $primary_values ));
        $result = $this->db->where_in($this->primary_key, $id)
                           ->delete($this->_table);
        $this->_run_after_callbacks('delete', array( $primary_values, $result ));
        
        return $result;
    }
    
    /**
     * Retrieve and generate a dropdown-friendly array of the data
     * in the table based on a key and a value.
     *
     * @return void
     */
    function dropdown()
    {
        $args =& func_get_args();
        
        if(count($args) == 2)
        {
            list($key, $value) = $args;
        }
        else
        {
            $key = $this->primary_key;
            $value = $args[0];
        }
        
        $this->_run_before_callbacks('get', array( $key, $value ));
        
        $result = $this->db->select(array($key, $value))
                           ->get($this->_table)
                           ->result();
                           
        $result = $this->_run_after_callbacks('get', array( $key, $value, $result ));
        
        $options = array();
        
        foreach ($result as $row)
        {
            $options[$row->{$key}] = $row->{$value};
        }
        
        return $options;
    }
    
    /**
     * Orders the result set by the criteria,
     * using the same format as CI's AR library.
     *
     * @param string $criteria The criteria to order by
     * @return void
     * @since 1.1.2
     */
    public function order_by($criteria, $order = 'ASC')
    {
        $this->db->order_by($criteria, $order);
        return $this;
    }
    
    /**
     * Limits the result set by the integer passed.
     * Pass a second parameter to offset.
     *
     * @param integer $limit The number of rows
     * @param integer $offset The offset
     * @return void
     * @since 1.1.1
     */
    public function limit($limit, $offset = 0)
    {
        $this->db->limit($limit, $offset);
        return $this;
    }

    /**
     * Tells the class to skip the insert validation
     *
     * @return void
     */
    public function skip_validation()
    {
        $this->skip_validation = TRUE;
        return $this;
    }

    /**
     * Return the next Auto Increment of the table
     *
     * @access        public
     * @param        void
     * @return        int     next id
     */
    public function get_next_id()
    {
        return (int) $this->db->select('AUTO_INCREMENT')
            ->from('information_schema.TABLES')
            ->where('TABLE_NAME', $this->_table)
            ->where('TABLE_SCHEMA', $this->db->database)->get()->row()->AUTO_INCREMENT;
    }
    
    /**
     * Run the before_ callbacks, each callback taking a $data
     * variable and returning it
     */
    private function _run_before_callbacks($type, $params = array())
    {
        $name = 'before_' . $type;
        
        if (!empty($name))
        {
            $data = (isset($params[0])) ? $params[0] : FALSE;
        
            foreach ($this->$name as $method)
            {
                $data += call_user_func_array(array($this, $method), $params);
            }
        }
        
        return $data;
    }
    
    /**
     * Run the after_ callbacks, each callback taking a $data
     * variable and returning it
     */
    private function _run_after_callbacks($type, $params = array())
    {
        $name = 'after_' . $type;
        
        if (!empty($name))
        {
            $data = (isset($params[0])) ? $params[0] : FALSE;
        
            foreach ($this->$name as $method)
            {
                $data = call_user_func_array(array($this, $method), $params);
            }
        }
        
        return $data;
    }
    
    /**
     * Runs validation on the passed data.
     *
     * @return bool
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
     * Fetches the table from the pluralised model name.
     *
     * @return void
     */
    private function _fetch_table()
    {
        if ($this->_table == NULL)
        {
            $class = preg_replace('/(_m|_model)?$/', '', get_class($this));
            
            $this->_table = plural(strtolower($class));
        }
    }
    
    /**
     * Sets where depending on the number of parameters
     *
     * @return void
     */
    private function _set_where($params)
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
}
