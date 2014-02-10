A Config Loader in the style of Laravel 4.0 Config
======

Usage:

~~~
app
|
|__config
|  |
|  |____ production
|  |        |
|  |        |_______ server1
|  |        |       |___ redis.php
|  |        |       |___ database.php
|  |        |
|  |        |_______ server2
|  |        |       |___ database.php
|  |        |
|  |        |_______ database.php
|  |
|  |____ app.php
|  |____ database.php
|  |____ redis.php

~~~

~~~PHP
<?php
// in database.php

return array(
    'config_value' => 'foo',
    'config_value2' => 'bar'
);

~~~

~~~PHP
<?php
// in production/database.php

return array(
    'config_value' => 'baz',
);

~~~

~~~PHP
<?php
// in production/server1/database.php

return array(
    'new_config_only_for_server1' => 'boo',
);

~~~

~~~PHP

$environment = '';

$config = new Repository(new FileLoader(__DIR__ . '/config'), $environment);

var_dump($config['database']);
/*
array(
   'config_value' => 'foo',
   'config_value2' => 'bar'
);
*/

//________________________________________________________________________

$environment = 'production.server1';

$config = new Repository(new FileLoader(__DIR__ . '/config'), $environment);

var_dump($config['database']);
/*
array(
   'config_value' => 'baz',
   'config_value2' => 'bar',
   'new_config_only_for_server1' => 'boo',
);
*/

~~~
