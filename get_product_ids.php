<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;

$products = Product::whereIn('slug', [
    'organic-green-tea-matcha',
    'organic-whole-wheat-flour',
    'organic-millet-hulled'
])->get(['id', 'slug', 'name']);

foreach ($products as $p) {
    echo "{$p->id} - {$p->slug} - {$p->name}\n";
}
