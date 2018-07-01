<?php
/**
 * @author      Roland Schilffarth <roland@schilffarth.org>
 * @license     https://www.gnu.org/licenses/gpl-3.0.de.html General Public License (GNU 3.0)
 */

namespace Source;

use Source\Command\AbstractCommand;
use Source\Component\Argument\AbstractArgumentObject;
use Source\Component\Interaction\Input\InputFactory;
use Source\Component\Interaction\Output\Output;

/**
 * Executing the command requested by the CLI
 */
class App
{

    const PAD_LENGTH = [
        'commands' => 50,
        'arguments' => 30,
        'aliases' => 15
    ];

    /**
     * @var AbstractCommand[]
     */
    private $commands = [];

    /**
     * @var AbstractCommand
     */
    private $exec = '';

    /**
     * Used to determine the duration of script execution
     */
    private $startTime = 0;

    /**
     * Whether the command has run successfully
     */
    private $success = false;

    /**
     * Console args
     * @var string[]
     */
    private $argv;

    private $output;
    private $inputFactory;

    public function __construct(
        Output $output,
        InputFactory $inputFactory
    ) {
        $this->output = $output;
        $this->inputFactory = $inputFactory;
    }

    /**
     * Handle a command, general execution
     */
    public function execute(array $argv)
    {
        register_shutdown_function([$this, 'end']);
        $this->startTime = microtime(true);
        $this->argv = $argv;

        if (strncmp(strtoupper(PHP_OS), 'WIN', 3) === 0) {
            $this->output->comment('Warning: CLI application run on windows might not support colored output! You can use --disable-colors');
            // todo Add a labeled input object that will ask the user whether to disable colors or keep them enabled
        }

        // All commands located at cli/Command/* are initialized
        $this->initializeCommands();
        // Initialize arguments for the registered commands
        $this->initializeArguments();
        // Run all initialized arguments for the command
        $this->exec->triggerArguments();

        // Execute the command
        if ($this->exec->run()) {
            $this->success = true;
        }
    }

    /**
     * Command finished
     */
    public function end()
    {
        if ($this->success) {
            $this->output->nl(2)->info('Success after ' . $this->getExecutionDuration());
        } else {
            $this->output->nl(2)->error('Failure after ' . $this->getExecutionDuration());
        }

        exit;
    }

    /**
     * How long the script has run yet (in seconds)
     */
    public function getExecutionDuration()
    {
        return round(microtime(true) - $this->startTime, 3) . ' seconds';
    }

    /**
     * Scan and initialize all valid commands, located at `cli/Command/*`
     */
    private function initializeCommands()
    {
        $scan = BASE . '/cli/Command';
        $paths = scandir($scan);
        foreach ($paths as $path) {
            $file = $scan . '/' . $path;
            if (is_file($file)) {
                try {
                    $this->registerCommand($file);
                } catch (\Exception $e) {
                    $this->output->error($e->getMessage());
                    exit;
                }
            }
        }
    }

    /**
     * Register the currently iterated command
     */
    private function registerCommand(string $file)
    {
        $class = 'Command\\' . basename($file, '.php');
        $register = inject($class);
        $this->commands[$register->command] = $register;
    }

    /**
     * Initialize the arguments that have been passed to the console
     */
    private function initializeArguments()
    {
        if (empty($this->argv)) {
            $this->output->error('No command desired to be run.');
            $this->listRegisteredCommands();
            $this->success = true;
            exit;
        }

        // First entry is always supposed to be the run command
        $command = array_shift($this->argv);

        if (isset($this->commands[$command])) {
            $this->exec = $this->commands[$command];
            $this->exec->setDefaultArgs();
            // Let the command do stuff that needs to be done before the passed console arguments are processed
            $this->exec->init();

            $this->registerArguments();
            $this->validateArguments();

            if (!empty($this->argv)) {
                $this->output->error('The following argument(s) could not be resolved: ');
                foreach ($this->argv as $arg) {
                    $this->output->writeln($arg);
                }
            }
        } else {
            $this->output->error('Command "' . $command . '"" not found.');
            $this->listRegisteredCommands();
            exit;
        }
    }

    /*
     * Process and register command args
     */
    private function registerArguments()
    {
        foreach ($this->exec->arguments as $argument) {
            $scans = [$argument->name];

            foreach ($argument->aliases as $alias) {
                $scans[] = $alias;
            }

            foreach ($scans as $scan) {
                // Scan whether the argument has been passed
                $found = array_search($scan, $this->argv, true);
                if ($found !== false) {
                    if ($argument->passed) {
                        // AbstractArgumentObject is passed multiple times
                        $this->output->error(sprintf('AbstractArgumentObject %s is passed more than once. Please make sure your command does not contain any typos.'), $argument->name);
                        exit;
                    }
                    $argument->passed = true;
                    unset($this->argv[$found]);
                }
            }

            $argument->launch($this->argv);
        }
    }

    /**
     * Check requires / excludes / dependencies for the arguments
     * For more information:
     *
     * @see AbstractArgumentObject::requires
     * @see AbstractArgumentObject::excludes
     */
    private function validateArguments()
    {
        foreach ($this->exec->arguments as $argument) {
            if (!$argument->passed) {
                continue;
            }

            foreach ($argument->excludes as $excl) {
                $exists = $this->exec->getArgKeyByProperty('name', $excl);
                if ($exists === false) {
                    $this->output->error(sprintf('Exclude %s for %s is not a valid argument.', $excl, $argument->name));
                    exit;
                }
                if ($this->exec->arguments[$exists]->passed) {
                    $this->output->error(sprintf('Cannot set both %s and %s! The arguments exclude each other.', $excl, $argument->name));
                    exit;
                }
            }

            foreach ($argument->requires as $req) {
                $exists = $this->exec->getArgKeyByProperty('name', $req);
                if ($exists === false) {
                    $this->output->error(sprintf('Require %s for %s is not a valid argument.', $excl, $argument->name));
                    exit;
                }
                if (!$this->exec->arguments[$exists]->passed) {
                    $this->output->error(sprintf('%s requires %s.', $argument->name, $req));
                    exit;
                }
            }
        }
    }

    /**
     * Lists all available commands that have been registered successfully
     */
    private function listRegisteredCommands()
    {
        $this->output->nl()->writeln('Here is a list of all available commands:')->nl();

        foreach ($this->commands as $command => $instance) {
            $this->output->writeln(str_pad(sprintf('<info>%s</info>', $command), self::PAD_LENGTH['commands']) . $instance->help);
        }
    }

}
