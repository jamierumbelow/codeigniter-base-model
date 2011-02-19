codeigniter-base-model
=====================================

Introduction
------------

My CodeIgniter Base Model is an extended CI_Model class to use in your CodeIgniter applications. It provides a full CRUD base to make developing database interactions easier and quicker. It also includes a bunch of other cool stuff, including before and after create callbacks, validation and a some table name guessing.

Usage
-----

Drag the MY\_Model.php file into your _application/core_ folder. CodeIgniter will load and initialise this class automatically for you. Extend all your model classes from MY_Model and all the functionality will be baked into your models automatically.

Naming Conventions
------------------

This class will try to guess the name of the table to use, by guessing the plural of the class name. If the table name isn't the plural and you need to set it to something else, just declare the _$\_table_ instance variable and set it to the table name. Some of the CRUD functions also assume that your primary key ID column is called _'id'_. You can overwrite this functionality by setting the _$primary\_key_ instance variable.

Validation
----------

This class also includes some excellent validation support. This uses the built-in Form Validation library and provides a wrapper around it to make validation automatic on insert. To enable, set the *$validate* instance variable to the rules array that you would pass into `$this->form_validation->set_rules()`. To find out more about the rules array, please [view the library's documentation](http://codeigniter.com/user_guide/libraries/form_validation.html#validationrulesasarray).

Upcoming Features
-----------------

* Before and after update callbacks
* Better table name guessing
* Better support for associations and JOINs