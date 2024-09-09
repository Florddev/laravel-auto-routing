<?php

namespace Florddev\LaravelAutoRouting\Console;

use Illuminate\Routing\Console\ControllerMakeCommand as BaseControllerMakeCommand;
use Symfony\Component\Console\Input\InputOption;

class ExtendedControllerMakeCommand extends BaseControllerMakeCommand
{
    protected function getOptions()
    {
        $options = parent::getOptions();

        // Ajout de l'option --auto
        $options[] = ['auto', null, InputOption::VALUE_NONE, 'Generate a controller with auto-routing methods'];

        return $options;
    }

    protected function getStub()
    {
        if ($this->option('auto')) {
            return $this->option('resource')
                ? __DIR__ . '/stubs/controller.auto.resource.stub'
                : __DIR__ . '/stubs/controller.auto.stub';
        }

        return parent::getStub();
    }
}
