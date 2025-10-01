<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MakeCustomScribeDocs extends Command
{
    protected $signature = 'scribe:custom-yaml';

    protected $description = 'Load custom scribe documentation (from yaml files)';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        foreach (config('custom_scribe') as $key => $yamlContent) {
            // overwrites if existing
            @file_put_contents(
                base_path('.scribe/endpoints/custom.' . $key . '.yaml'),
                $yamlContent
            );
        }

        $this->info('Please run scribe:generate again to see the changes.');

        return 0;
    }
}
