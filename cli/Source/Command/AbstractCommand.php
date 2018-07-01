<?php
/**
 * @author      Roland Schilffarth <roland@schilffarth.org>
 * @license     https://www.gnu.org/licenses/gpl-3.0.de.html General Public License (GNU 3.0)
 */

namespace Source\Command;

use Source\App;
use Source\Component\Argument\AbstractArgumentObject;
use Source\Component\Argument\ArgumentFactory;
use Source\Component\Interaction\Input\AbstractInputObject;
use Source\Component\Interaction\Input\InputFactory;
use Source\Component\Interaction\Output\Output;

/**
 * Extend this class and define your command by the given properties.
 */
abstract class AbstractCommand
{

    /**
     * Command initialization
     */
    public $command = '';

    /**
     * General message for your command that is shown in the summary help message of all available commands
     */
    public $help = '';

    /**
     * Arguments that won't be executed by default, they need to be triggered manually with @see AbstractArgumentObject::trigger
     *
     * @var AbstractArgumentObject[]
     */
    public $arguments = [];

    protected $input;
    protected $output;
    protected $argumentFactory;
    protected $inputFactory;

    public function __construct(
        AbstractInputObject $input,
        Output $output,
        ArgumentFactory $argumentFactory,
        InputFactory $inputFactory
    ) {
        $this->input = $input;
        $this->output = $output;
        $this->argumentFactory = $argumentFactory;
        $this->inputFactory = $inputFactory;
    }

    /**
     * Register arguments and associated handlers
     * For example usage @see AbstractCommand::setDefaultArgs
     */
    abstract public function init();

    /**
     * The function is called if your command is run
     */
    abstract public function run();

    /**
     * Register default arguments
     */
    public function setDefaultArgs()
    {
        /** COLORED OUTPUT - Whether to display console colored output */
        $disableColoredOutput = $this->argumentFactory->create(
            ArgumentFactory::ARGUMENT_SIMPLE,
            '--color-disable',
            'Disable colors displayed with the console output. Recommended for Windows PowerShell.'
        );
        $disableColoredOutput->registerHandler([$this, 'disableColoredOutput']);
        // Set the key -99 in order to prioritize this argument as the very first
        $this->setArgument($disableColoredOutput, -99);

        /** HELP */
        $help = $this->argumentFactory->create(
            ArgumentFactory::ARGUMENT_SIMPLE,
            'help',
            'Get detailed help for the command. Displays further information and example usages.',
            'h'
        );
        $help->registerHandler([$this, 'triggerHelp']);
        // Set the key -98 in order to prioritize this argument secondly
        $this->setArgument($help, -98);

        /** VERBOSITY LEVELS */
        $debug = $this->argumentFactory->create(
            ArgumentFactory::ARGUMENT_SIMPLE,
            'debug',
            'Enable debugging. Displays all messages.',
            'd'
        );
        $debug->registerHandler([$this, 'setVerbosityDebug'])
            ->excludes('quiet');
        $this->setArgument($debug);

        $quiet = $this->argumentFactory->create(
            ArgumentFactory::ARGUMENT_SIMPLE,
            'quiet',
            'Suppress both normal and debugging messages. Only errors will be outputted.',
            'q'
        );
        $quiet->registerHandler([$this, 'setVerbosityQuiet'])
            ->excludes('debug');
        $this->setArgument($quiet);
    }

    public function triggerHelp()
    {
        $this->output->info($this->command)->nl();
        foreach ($this->arguments as $argument) {
            // Display detailed help for the command
            $aliasesStr = '';
            foreach ($argument->aliases as $alias) {
                $aliasesStr .= "  " . $alias;
            }
            $this->output->writeln(
                str_pad($argument->name, App::PAD_LENGTH['arguments'])
                . "\t" . str_pad($aliasesStr, App::PAD_LENGTH['aliases'])
                . "\t" . $argument->description
            );
        }

        // Do not run any
        exit;
    }

    public function setVerbosityDebug()
    {
        $this->output->verbosity = Output::DEBUG;
    }

    public function setVerbosityQuiet()
    {
        $this->output->verbosity = Output::QUIET;
    }

    public function disableColoredOutput()
    {
        $this->output->colorDisabled = true;
    }

    /**
     * Trigger all initialized arguments
     */
    public function triggerArguments()
    {
        foreach ($this->arguments as $argument) {
            if ($argument->passed) {
                $argument->trigger();
            }
        }
    }

    /**
     * Retrieve the key for the desired argument
     * @param string $property The property to filter
     * @param string $find The value to find in $property
     *
     * Example:
     *
     * $property = 'name';
     * $find = 'help';
     *
     * => This will try to find an argument with the name 'help' - Match would be Argument->name === 'help';
     *
     * @return false|int|mixed The key of the found argument
     */
    public function getArgKeyByProperty(string $property, string $find)
    {
        return array_search($find, array_combine(
            array_keys($this->arguments),
            array_column($this->arguments, $property)
        ));
    }

    /**
     * Set an argument for the command
     * You can set the order of
     */
    protected function setArgument(AbstractArgumentObject $arg, string $order = null): self
    {
        if ($order === null) {
            $this->arguments[] = $arg;
        } else {
            if (isset($this->arguments[$order])) {
                $this->error(sprintf('Cannot initialize argument %s properly ordered as %s. The given order has already been set.', $arg->name, $order));
                $this->arguments[] = $arg;
            } else {
                $this->arguments[$order] = $arg;
                ksort($this->arguments);
            }
        }

        return $this;
    }

    /**
     * Retrieve an argument by its name
     */
    protected function getArgument(string $name): AbstractArgumentObject
    {
        $name = $this->getArgKeyByProperty('name', AbstractArgumentObject::trimProperty($name));

        if (!isset($this->arguments[$name])) {
            $this->error(sprintf('%s could not be found as a registered argument. Please make sure you do not have any typos.', $name));
        }

        return $this->arguments[$name];
    }

    /**
     * Remove / kill / destroy an argument
     */
    protected function destroyArgument(string $name): self
    {
        unset($this->arguments[$this->getArgKeyByProperty('name', AbstractArgumentObject::trimProperty($name))]);

        return $this;
    }

    /*******************
     * Console output *
     ******************/

    protected function writeln(string $message = '', $verbosity = Output::NORMAL): Output
    {
        return $this->output->writeln($message, $verbosity);
    }

    protected function error(string $message = '', $verbosity = Output::QUIET): Output
    {
        return $this->output->error($message, $verbosity);
    }

    protected function info(string $message = '', $verbosity = Output::NORMAL): Output
    {
        return $this->output->info($message, $verbosity);
    }

    protected function debug(string $message = '', $verbosity = Output::DEBUG): Output
    {
        return $this->output->debug($message, $verbosity);
    }

}
