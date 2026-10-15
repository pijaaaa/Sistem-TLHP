<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ActionPlan;
use App\Models\Finding;

$finding = Finding::withoutGlobalScopes()->find(3);
echo "Finding 3 status: " . $finding->status->value . "\n";

ActionPlan::withoutGlobalScopes()->where('finding_id', 3)->get(['id','code','status','current_revision'])->each(function ($ap) {
    echo $ap->code . " | status=" . $ap->status->value . " | revision=" . $ap->current_revision . "\n";
});