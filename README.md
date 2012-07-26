codeigniter-base-model
=====================================

[![Build Status](https://secure.travis-ci.org/jamierumbelow/codeigniter-base-model.png?branch=master)](http://travis-ci.org/jamierumbelow/codeigniter-base-model)

My CodeIgniter Base Model is an extended CI_Model class to use in your CodeIgniter applications. It provides a full CRUD base to make developing database interactions easier and quicker. It also includes a bunch of other cool stuff, including before and after create callbacks, validation and a some table name guessing.

Synopsis
--------

```php
class Post_model extends MY_Model { }

$this->load->model('post_model', 'post');

$this->post->get_all();

$this->post->get(1);
$this->post->get_by('title', 'Pigs CAN Fly!');
$this->post->get_many_by('status', 'open');

$this->post->insert(array(
    'status' => 'open',
    'title' => "I'm too sexy for my shirt"
));

$this->post->update(1, array( 'status' => 'closed' ));

$this->post->delete(1);
```

Installation/Usage
------------------

I recommend you use [Composer](http://getcomposer.org/) to install **MY_Model**. Install Composer for your project:

    $ curl -s http://getcomposer.org/installer | php

...and create/edit your `composer.json`:

    {
        "require": {
            "jamierumbelow/codeigniter-base-model": "*"
        }
    }

...and install it!

    $ php composer.phar install

Remember to include Composer's autoload file in `index.php`:

    require_once './vendor/autoload.php';

Alternatively, download and drag the MY\_Model.php file into your _application/core_ folder. CodeIgniter will load and initialise this class automatically for you. Extend all your model classes from MY_Model and all the functionality will be baked into your models automatically.

Naming Conventions
------------------

This class will try to guess the name of the table to use, by guessing the plural of the class name. If the table name isn't the plural and you need to set it to something else, just declare the _$\_table_ instance variable and set it to the table name. Some of the CRUD functions also assume that your primary key ID column is called _'id'_. You can overwrite this functionality by setting the _$primary\_key_ instance variable.

Callbacks
---------

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

Validation
----------

This class also includes some excellent validation support. This uses the built-in Form Validation library and provides a wrapper around it to make validation automatic on insert. To enable, set the *$validate* instance variable to the rules array that you would pass into `$this->form_validation->set_rules()`. To find out more about the rules array, please [view the library's documentation](http://codeigniter.com/user_guide/libraries/form_validation.html#validationrulesasarray).

Then, for each call to `insert()`, the data passed through will be validated according to the *$validate* rules array. **Unlike the CodeIgniter validation library, this won't validate the POST data, rather, it validates the data passed directly through.**

If for some reason you'd like to skip the validation, you can call `skip_validation()` before the call to `insert()` and validation won't be performed on the data for that single call.

Arrays vs Objects
-----------------

By default, MY_Model is setup to return objects using CodeIgniter's QB's `row()` and `result()` methods. If you'd like to use their array counterparts, there are a couple of ways of customising the model.

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


Unit Tests
----------

MY_Model contains a robust set of unit tests to ensure that the system works as planned.

**Currently, the tests only run on PHP5.4 or 5.3.**

Install [PHPUnit](https://github.com/sebastianbergmann/phpunit). I'm running version 3.6.10.

Then, simply run the `phpunit` command on the test file:

    $ phpunit tests/MY_Model_test.php


Other Documentation
-------------------

* Jeff Madsen has written an excellent tutorial about the basics (and triggered me updating the documentation here). [Read it now, you lovely people.](http://www.codebyjeff.com/blog/2012/01/using-jamie-rumbelows-my_model)
* Rob Allport wrote a post about MY_Model and his experiences with it. [Check it out!](http://www.web-design-talk.co.uk/493/codeigniter-base-models-rock/)

Contributors
------------

Special thanks to:
    
* [Phil Sturgeon](http://philsturgeon.co.uk)
* [Dan Horrigan](http://danhorrigan.com)
* [Adam Jackett](http://darkhousemedia.com)
    
...as well as everybody else who has contributed a great amount of code and ideas to this library

Changelog
---------

**Version 1.3.0 - IN DEVELOPMENT**
* Added support for array return types using `$return_type` variable and `as_array()` and `as_object()` methods
* Added PHP5.3 support for the test suite
* Removed the deprecated `MY_Model()` constructor
* Fixed an issue with after_create callbacks (thanks [zbrox](https://github.com/zbrox)!)
* Composer package will now autoload the file
* Fixed the callback example by returning the given/modified data (thanks [druu](https://github.com/druu)!)
* Change order of operations in `_fetch_table()` (thanks [JustinBusschau](https://github.com/JustinBusschau)!)

**Version 1.2.0**
* Bugfix to `update_many()`
* Added getters for table name and skip validation
* Fix to callback functionality (thanks [titosemi](https://github.com/titosemi)!)
* Vastly improved documentation
* Added a `get_next_id()` method (thanks [gbaldera](https://github.com/gbaldera)!)
* Added a set of unit tests
* Added support for [Composer](http://getcomposer.org/)

**Version 1.0.0 - 1.1.0**
* Initial Releases