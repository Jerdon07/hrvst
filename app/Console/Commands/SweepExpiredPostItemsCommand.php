<?php

namespace App\Console\Commands;

use App\Actions\PostItem\SweepExpiredPostItemsAction;
use Illuminate\Console\Command;

class SweepExpiredPostItemsCommand extends Command
{
    protected $signature = 'post-items:expire';

    protected $description = 'Backstop sweep: force-expire ongoing post items whose action window lapsed but were never displayed';

    public function handle(SweepExpiredPostItemsAction $sweepExpiredPostItem): int
    {
        $count = $sweepExpiredPostItem();

        $this->info("Expired {$count} post item(s).");

        return self::SUCCESS;
    }
}
