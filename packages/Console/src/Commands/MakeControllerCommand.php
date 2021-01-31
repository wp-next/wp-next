<?php

namespace WpNext\Console\Commands;

use Illuminate\Console\GeneratorCommand;

class MakeControllerCommand extends GeneratorCommand
{
    protected $name = 'make:controller';

    protected $description = 'Create a new controller class';

    protected $type = 'Controllers';

    protected function getStub()
    {
        return __DIR__.'/stubs/controller.php.stub';
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Controllers';
    }

    public function handle()
    {
        parent::handle();

        $this->doOtherOperations();
    }

    protected function doOtherOperations()
    {
        // Get the fully qualified class name (FQN)
        $class = $this->qualifyClass($this->getNameInput());

        // get the destination path, based on the default namespace
        $path = $this->getPath($class);

        $content = file_get_contents($path);

        // Update the file content with additional data (regular expressions)

        file_put_contents($path, $content);
    }
}
