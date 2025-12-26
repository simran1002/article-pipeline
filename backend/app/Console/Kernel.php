<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Console\Kernel as KernelContract;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;

class Kernel implements KernelContract
{
    protected $commands = [
        Commands\ScrapeArticles::class,
    ];

    public function __construct(
        protected Application $app,
        protected Dispatcher $events
    ) {}

    public function handle($input, $output = null)
    {
        return 0;
    }

    public function terminate($input, $status)
    {
        //
    }

    public function getArtisan()
    {
        return null;
    }

    protected function schedule(Schedule $schedule)
    {
        //
    }
}


