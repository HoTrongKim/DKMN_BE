<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();
$user = App\\Models\\NguoiDung::where('email','admin@dkmn.com')->first();
var_export($user ? $user->getAttributes()["mat_khau"] : 'not found');
