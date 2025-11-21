<?php
require __DIR__ . "/vendor/autoload.php";
$app = require __DIR__ . "/bootstrap/app.php";
$kernel = $app->make("Illuminate\\Contracts\\Console\\Kernel");
$kernel->bootstrap();
$trams = App\Models\Tram::where("loai","san_bay")->where("tinh_thanh_id",32)->pluck("ten");
foreach ($trams as $tram) {
    echo $tram . PHP_EOL;
}

