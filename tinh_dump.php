<?php
require __DIR__ . "/vendor/autoload.php";
$app = require __DIR__ . "/bootstrap/app.php";
$kernel = $app->make("Illuminate\\Contracts\\Console\\Kernel");
$kernel->bootstrap();
foreach (App\Models\TinhThanh::orderBy("id")->get() as $city) {
    printf("%02d: %s\n", $city->id, $city->ten);
}

