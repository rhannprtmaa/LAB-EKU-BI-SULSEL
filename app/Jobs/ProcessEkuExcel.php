<?php

namespace App\Jobs;

use App\Models\EkuTransaction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessEkuExcel implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public $transaction;

    /**
     * Create a new job instance.
     */
    public function __construct(EkuTransaction $transaction)
    {
        $this->transaction = $transaction;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Panggil fungsi parsing Excel yang ada di Model
        $this->transaction->reprocessExcelFiles();
    }
}
