<?php

namespace Shares;

require __DIR__ . '/../vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;

$capsule = new Capsule();

$env = parse_ini_file('.env');
$db = $env['SHARES_DATABASE'];
$username = $env['SHARES_USERNAME'];
$password = $env['SHARES_PASSWORD'];
$server = $env['SHARES_SERVER'];

$capsule->addConnection(
    [
    "driver" => "mysql",
    "host" => $server,
    "database" => $db,
    "username" => $username,
    "password" => $password,
    "trust_server_certificate" => 'true'
],
    "default"
);

$capsule->setAsGlobal();
$capsule->bootEloquent();
