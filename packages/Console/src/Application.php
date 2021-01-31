<?php

namespace WpNext\Console;

use Illuminate\Console\Application as ConsoleApp;
use Illuminate\Events\Dispatcher;
use WpNext\Core\Application as App;

class Application
{
    protected $artisan;

    public function __construct(string $dir)
    {
        $app = new App($dir);

        $events = new Dispatcher($app);
        
        $app->boot();
        
        $this->artisan = new ConsoleApp($app, $events, 'v1');
        
        $this->artisan->setName('wp-next');
    }

    public function run()
    {
        $this->artisan->resolveCommands([
            Commands\MakeControllerCommand::class,
            Commands\VendorPublishCommand::class,
        ]);

        $this->artisan->run();
    }
}
