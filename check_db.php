<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$items = \App\Models\KaporItem::where('item_name','like','%KEMEJA%')->orWhere('item_name','like','%BAJU%')->get();
foreach($items as $i) {
    echo $i->item_name . " : ";
    $sizes = $i->sizes()->where('gender', 'L')->pluck('size_label')->toArray();
    echo implode(', ', $sizes) . "\n";
}
