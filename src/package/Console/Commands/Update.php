<?php

namespace PragmaRX\Countries\Package\Console\Commands;

use PragmaRX\Countries\Package\Update\Updater;

class Update extends Base
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'countries:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update all data';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function fire()
    {
        app(Updater::class)->update($this);

        $this->info('Updated.');
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->fire();
    }
}
