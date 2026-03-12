<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$nrps = ['70040022', '79010791', '82090453', '86090028'];
$results = \App\Models\Personnel::with('satker')->whereIn('nrp', $nrps)->get(['id','nrp','full_name','satker_id'])->toArray();

print_r($results);
