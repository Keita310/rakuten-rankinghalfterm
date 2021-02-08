<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CreateRankingDataService;

class CreateRankingDataCommand extends Command
{


    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:create_ranking_data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '楽天半期ランキングデータ集計コマンド';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     * @return int
     */
    public function handle(CreateRankingDataService $service)
    {
        $service->handle();
        return 0;
    }
}
