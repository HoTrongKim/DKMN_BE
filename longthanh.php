<?php
require __DIR__ . "/vendor/autoload.php";
$app = require __DIR__ . "/bootstrap/app.php";
$kernel = $app->make("Illuminate\\Contracts\\Console\\Kernel");
$kernel->bootstrap();
$tram = App\Models\Tram::where("ten", "Sân bay Long Thành (LTN)")->first();
var_dump($tram?->ten, $tram?->tinh_thanh_id);

