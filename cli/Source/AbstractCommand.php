<?php
namespace Source;

/**
 * Extend this class and define your command by the given properties.
 */
abstract class AbstractCommand
{

    /**
     * Initialize your command here
     */
    public $command = '';

    /**
     * Declare a general message for your command that is shown in the help message
     */
    public $help = '';

    /**
     * Reinitializing this property will not provide any functionality of the arguments themselves
     * Adding description and aliases only provides detailed information when the user passes an argument -h or --help
     */
    public $expectedArgs = [
        // '--argument' => [
        //     'aliases' => [
        //         '-a'
        //     ],
        //     'description' => ''
        // ];
    ];

    protected $output;

    protected $input;

    public function __construct(
        Output $output,
        Input $input
    ) {
        $this->output = $output;
        $this->input = $input;
    }

    /**
     * The function that will be called for your command
     */
    abstract public function run(array $args);

    /**
     * @see Output::writeln
     */
    public function writeln(string $message = '', $verbosity = Output::NORMAL): Output
    {
        return $this->output->writeln($message, $verbosity);
    }

    /**
     * @see Output::error
     */
    public function error(string $message = '', $verbosity = Output::QUIET): Output
    {
        return $this->output->error($message, $verbosity);
    }

    /**
     * @see Output::info
     */
    public function info(string $message = '', $verbosity = Output::NORMAL): Output
    {
        return $this->output->info($message, $verbosity);
    }

    /**
     * @see Output::debug
     */
    public function debug(string $message = '', $verbosity = Output::DEBUG): Output
    {
        return $this->output->debug($message, $verbosity);
    }

}
