<?php
require __DIR__ . "/vendor/autoload.php";
$app = require __DIR__ . "/bootstrap/app.php";
$kernel = $app->make("Illuminate\\Contracts\\Console\\Kernel");
$kernel->bootstrap();
$query = App\Models\ChuyenDi::whereHas("nhaVanHanh", function($q){
    $q->where("loai","may_bay");
})->whereHas("tramDi", function($q){
    $q->where("tinh_thanh_id", 1);
})->whereHas("tramDen", function($q){
    $q->where("tinh_thanh_id", 32);
});
echo "count: " . $query->count() . PHP_EOL;
$trip = $query->orderBy("gio_khoi_hanh")->first();
if ($trip) {
    echo $trip->gio_khoi_hanh . "\n";
}

