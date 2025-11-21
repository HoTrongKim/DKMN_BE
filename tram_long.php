<?php
require __DIR__ . "/vendor/autoload.php";
$app = require __DIR__ . "/bootstrap/app.php";
$kernel = $app->make("Illuminate\\Contracts\\Console\\Kernel");
$kernel->bootstrap();
$trams = App\Models\Tram::where("ten", "like", "%Long%") ->get();
echo "count: " . $trams->count() . "\n";
foreach ($trams as $tram) {
    printf("%s (%s)\n", $tram->ten, $tram->tinh_thanh_id);
}

