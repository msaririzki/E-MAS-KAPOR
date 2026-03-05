<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Add SISWA-specific ranks
$siswaRanks = [
    ['name' => 'AKPOL', 'category' => 'SISWA', 'sort_order' => 40],
    ['name' => 'BA BRIMOB', 'category' => 'SISWA', 'sort_order' => 41],
];

foreach ($siswaRanks as $rank) {
    App\Models\Rank::updateOrCreate(
        ['name' => $rank['name']],
        $rank
    );
    echo "Rank '{$rank['name']}' (category: {$rank['category']}) added!\n";
}

echo "\nDone! SISWA ranks added.\n";
