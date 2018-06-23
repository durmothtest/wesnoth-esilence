<?php
namespace Source;

class Input
{

    /**
     * The label that will be displayed the next time a user input is requested with @see Input::nextLine
     */
    protected $label;

    protected $labelVerbosity = Output::QUIET;

    protected $output;

    public function __construct(
        Output $output
    ) {
        $this->output = $output;
    }

    public function setLabel(string $label, $verbosity = Output::QUIET): self
    {
        $this->label = $label;
        $this->labelVerbosity = $verbosity;

        return $this;
    }

    /**
     * Get the next line the user inputted
     */
    public function nextLine(): string
    {
        if (is_string($this->label)) {
            $this->output->writeln($this->label, $this->labelVerbosity);
        }

        return trim(fgets(STDIN));
    }

    /**
     * Get the next integer the user inputted
     */
    public function nextInt(): int
    {
        $input = $this->nextLine();

        if (is_numeric($input)) {
            return $input;
        }

        return $this->nextInt();
    }

    /**
     * Get the next boolean the user inputted
     */
    public function nextBool(): bool
    {
        return filter_var($this->nextLine(), FILTER_VALIDATE_BOOLEAN);
    }

}
