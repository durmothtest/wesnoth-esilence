<?php
namespace Source;

/**
 * Executing the command requested by the CLI
 */
class Run
{

    const PAD_LENGTH_COMMANDS = 50;

    const PAD_LENGTH_ARGS = 30;

    const PAD_LENGTH_ALIASES = 15;

    /**
     * @var AbstractCommand[]
     */
    private $commands;

    private $output;

    public function __construct(
        Output $output
    ) {
        $this->output = $output;
    }

    /**
     * Handle a command, general execution
     */
    public function execute(array $argv)
    {
        $this->init();
        // First argument determines the called command
        $command = array_shift($argv);
        $argv = $this->setVerbosity($argv);
        $this->output->nl();

        if (isset($this->commands[$command])) {
            // Run the desired command
            $this->output->debug('Running ' . $command)->nl();
            try {
                $this->call($command, $argv);
            } catch (\Exception $e) {
                $this->output->error($e->getMessage());
                exit();
            }
        } else {
            // List all available commands
            $this->output->error('Command "' . $command . '" not found.')->nl();
            $this->output->writeln('Here is a list of all available commands:')->nl();
            foreach ($this->commands as $command => $instance) {
                $this->output->writeln(str_pad("<info>$command</info>:", self::PAD_LENGTH_COMMANDS) . $instance->help);
            }
            $this->output->nl()->info('For detailed information, use <command> -h --help')
                ->info('Enable debug mode with <command> -d --debug')
                ->info('Disable all console output beside errors with <command> -q --quiet');
        }
        $this->output->nl();
    }

    /**
     * Set the verbosity level for the run command
     */
    private function setVerbosity(array $args): array
    {
        $isSet = false;
        $verbosityArguments = [
            // Enable QUIET verbosity, disables all normal and debug messages
            '-q' => Output::QUIET,
            '--quiet' => Output::QUIET,
            // Enable DEBUG verbosity, enable and output all messages
            '-d' => Output::DEBUG,
            '--debug' => Output::DEBUG,
        ];

        foreach ($verbosityArguments as $argument => $level) {
            if (($key = array_search($argument, $args)) !== false) {
                if ($isSet) {
                    $this->output->error('Cannot set verbosity level both quiet and debug at once.');
                    exit();
                } else {
                    $this->output->verbosity = $level;
                    unset($args[$key]);
                    $isSet = true;
                }
            }
        }

        return $args;
    }

    /**
     * Scan and initialize all valid commands, located at `cli/Command/*`
     */
    private function init()
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
                    exit();
                }
            }
        }
    }

    /**
     * Register the currently iterated command
     * @throws \Exception if the class couldn't be injected
     */
    private function registerCommand(string $file)
    {
        $class = 'Command\\' . basename($file, '.php');
        $register = inject($class);
        $this->commands[$register->command] = $register;
    }

    /**
     * Run the specific command
     */
    private function call(string $command, array $args)
    {
        if (array_search('-h', $args) !== false || array_search('--help', $args) !== false) {
            $this->output->info($command)->nl();
            foreach ($this->commands[$command]->expectedArgs as $arg => $info) {
                // Display detailed help for the command
                $aliasesStr = '';
                foreach ($info['aliases'] as $alias) {
                    $aliasesStr .= "  " . $alias;
                }
                $this->output->writeln(
                    str_pad($arg, self::PAD_LENGTH_ARGS)
                    . "\t" . str_pad($aliasesStr, self::PAD_LENGTH_ALIASES)
                    . "\t" . $info['description']
                )->nl();
            }
        } else {
            // Execute command
            if ($this->commands[$command]->run($args)) {
                $this->output->writeln()->info('Success')->nl();
            } else {
                $this->output->writeln()->error('Failure')->nl();
            }
        }
    }

}
