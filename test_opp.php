<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$supriyono = current(\App\Models\Personnel::where('full_name', 'like', '%SUPRIYONO%')->get()->toArray());
$rusdi = current(\App\Models\Personnel::where('full_name', 'like', '%RUSDI%')->get()->toArray());

echo "Supriyono:\n";
print_r($supriyono);
echo "\nRusdi:\n";
print_r($rusdi);
