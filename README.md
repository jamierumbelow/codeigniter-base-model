#CodeIgniter MongoDB Base Model
CodeIgniter base model with optional MongoDB support.

It's based on the work of [Jamie Rumbelow's CRUD model](http://github.com/jamierumbelow/codeigniter-base-model)

##Synopsis

```php
class Post_model extends MY_Model {

    // Indicates that model persists in MongoDB
    protected $_mongodb = TRUE;
}

// Load model
$this->load->model('post_model', 'post');

// Get all records/documents
$posts = $this->post->get_all();

// Get a document by its '_id' value
$this->post->get('4fc6a54197ab4f194caa4a77');

// Get a record/document by its 'title' value
$this->post->get_by('title', 'Pigs CAN Fly!');

// Get all records/documents by their 'status' value
$this->post->get_many_by('status', 'open');

// Insert a new record/document
$this->post->insert(array(
    'status' => 'open',
    'title' => "I'm too sexy for my shirt"
));

// Update a record
$this->post->update(1, array( 'status' => 'closed' ));

// Delete a document
$this->post->delete('4fc6a54197ab4f194caa4a77');
```

##Requirements
[CodeIgniter MongoDB Active Record Library](https://github.com/alexbilbie/codeigniter-mongodb-library/tree/v2)

##Installation
* Move `MY_Model.php` file into your `application/core` folder.
* Alter your models to extend `MY_Model` instead of `CI_Model` class.

##Naming Conventions
This class will try to guess the name of the table ot collection to use, by guessing the plural of the model class name. If the table or collection name isn't the plural and you need to set it to something else, just declare the `$_datasource` instance variable and set it to the table or collection name. Some of the CRUD functions also assume that your primary key ID column is called `id`. You can overwrite this functionality by setting the `$primary_key` instance variable. It's forced to `_id` when using MongoDB.

##Callbacks
There are many times when you'll need to alter your model data before it's inserted or returned. This could be adding timestamps, pulling in relationships or deleting dependent rows. The MVC pattern states that these sorts of operations need to go in the model. In order to facilitate this, **MY_Model** contains a series of callbacks -- methods that will be called at certain points.

The full list of callbacks are as follows:

* $before_create
* $after_create
* $before_update
* $after_update
* $before_get
* $after_get
* $before_delete
* $after_delete

These are instance variables usually defined at the class level. They are arrays of methods on this class to be called at certain points. An example:

```php
class Book_model extends MY_Model
{
    public $before_create = array( 'timestamps' );

    protected function timestamps($book)
    {
        $book['created_at'] = $book['updated_at'] = date('Y-m-d H:i:s');
        return $book;
    }
}
```

##Validation
This class also includes some excellent validation support. This uses the built-in Form Validation library and provides a wrapper around it to make validation automatic on insert. To enable, set the *$validate* instance variable to the rules array that you would pass into `$this->form_validation->set_rules()`. To find out more about the rules array, please [view the library's documentation](http://codeigniter.com/user_guide/libraries/form_validation.html#validationrulesasarray).

Then, for each call to `insert()`, the data passed through will be validated according to the *$validate* rules array. **Unlike the CodeIgniter validation library, this won't validate the POST data, rather, it validates the data passed directly through.**

If for some reason you'd like to skip the validation, you can call `skip_validation()` before the call to `insert()` and validation won't be performed on the data for that single call.

##Arrays vs Objects
By default, MY_Model is setup to return objects. If you'd like to use their array counterparts, there are a couple of ways of customising the model.

If you'd like all your calls to use the array methods, you can set the `$return_type` variable to `array`.

    class Book_model extends MY_Model
    {
        protected $return_type = 'array';
    }

If you'd like just your _next_ call to return a specific type, there are two scoping methods you can use:

    $this->book_model->as_array()
                     ->get(1);
    $this->book_model->as_object()
                     ->get_by('column', 'value');