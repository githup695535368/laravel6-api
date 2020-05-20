<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
class RunFile extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'run';


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '[MicroCut] Run php file with system env support.';

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
     *
     * @return mixed
     */
    public function handle()
    {
        $file = $this->argument('php_file');
        if (is_file($file)) {
            define('ERROR_NOT_REPORT', true);

            setOperatorName("runFile");

            include $file;
        } else {
            $this->error("file '{$file}' not found!");
        }
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return array(
            array('php_file', InputArgument::REQUIRED, 'The php file to run with Laputa.'),
            array('args', InputArgument::OPTIONAL, 'Other args.'),
        );
    }
}
