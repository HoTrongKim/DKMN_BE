<?php
require __DIR__ . "/vendor/autoload.php";
$app = require __DIR__ . "/bootstrap/app.php";
$kernel = $app->make("Illuminate\\Contracts\\Console\\Kernel");
$kernel->bootstrap();
echo App\Models\ChuyenDi::whereHas("nhaVanHanh", function($q){
    $q->where("loai","may_bay");
})->count();

