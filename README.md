
HARLEQUIN
=========

An automatic testing framework for busy developers.

What it is
----------

Harlequin allows you to quickly and simply test REST/JSON API responses,
without writing almost any code manually.

Modern apps mix different types of testing. 
Harlequin doesn't replace TDD, unit tests, integration tests, or anything else.
Harlequin adds one extra layer of defect detection and is incredibly simple to
use.

How it works
------------

Harlequin watches the inbound requests to your API and generates PHP tests
automatically. You can rerun those tests later, after modifying them to suit your needs.

Harlequin checks HTTP response status codes, content length, and, for JSON
endpoints, the general structure of the returned data structure. Those instructions
for response verification are all contained within the auto-generated test description
file, which is itself a PHP script. 

Response values are inherited by subsequent calls. (TODO - busted at the moment)

You can run tests individually, or all at once. You can configure settings for all tests in
local-{ENV}.php or config.php in the same folder as the tests.

Benefits
--------

* Extremely fast to setup. No writing tests.

* Tests are simple and explicit, and are defined as data - with programmable hooks.

* Tests are just PHP code, so easy to edit to your needs. 

* Need custom login/logout logic? Throw it in there - it's just PHP.

* Need to rebang the database before you start testing? Add it to the first test's prepareCallback.

* Test finnicky and annoying? rm the sucker.

* Want to add a fuzzer? Edit a field's 'data' callback and mess things up.

How the tests actually work
---------------------------

Your test configures what you expect back from the remote side. We
automatically build a guess at what the expected data should be. This can be
found inside the "expect" field inside the test object found in your code.

I suggest looking one over. You'll find it quite obvious:

fillmein

Step 1: Collect tests
---------------------

In your PHP app's master controller file (i.e., the main entry point for the
app), include Harlequin before anythign else, and allow it to record all
requests. Output is sent to a directory that must be writable by the web
process. Just use /tmp/harlequin and get on with your life.


````
	require('harlequin/harlequin.php');
	Harlequin::record('/tmp/harlequin/');
````

Harlequin will bomb if this directory is not writable.

Files will be generated like $dir/t-(time)-(endpoint).php

Step 2: Manually refine tests (optional)
----------------------------------------

Edit to your heart's delight. 

Step 3: Run tests
-----------------

Execute the PHP files individually, or all at once:

````
$ php harlequin.php run /tmp/harlequin/
````

Limitations
-----------

* Requires $_SERVER['REQUEST_URI'] to be logical.
* Limited testing for non-JSON endpoints (only status code and min/max content lengths).

Cookbook
--------

Point your tests at another site:

Create a config.php file and add a line like this:

````
$testConfig['urlPrefix'] = 'https://other-api-instance/';
````

TODO
----

* Right now, every return value gets stored. That's a waste. Support store == false

