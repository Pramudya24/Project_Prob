<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\RombonganItem;

class FixRombonganFieldVerifications extends Command
{
    protected $signature = 'rombongan:fix-verifications';
    protected $description = 'Initialize field verifications for all rombongan items';

    public function handle()
    {
        $items = RombonganItem::with('item')->get();
        
        $this->info("Processing {$items->count()} rombongan items...");
        
        $bar = $this->output->createProgressBar($items->count());
        
        foreach ($items as $item) {
            if ($item->item) {
                $item->initializeAllFieldVerifications();
            }
            $bar->advance();
        }
        
        $bar->finish();
        $this->info("\nDone! All field verifications initialized.");
        
        return Command::SUCCESS;
    }
}