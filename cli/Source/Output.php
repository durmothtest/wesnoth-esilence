<?php
namespace Source;

class Output
{

    /**
     * Output is always displayed, even when --quiet is active
     */
    const QUIET = 1;

    /**
     * Default output level
     */
    const NORMAL = 2;

    /**
     * Verbose information, displayed when --verbose is active
     */
    const DEBUG = 3;

    /**
     * The current verbosity level for the run command
     * @see Output::verbosityDisallowsOutput
     */
    public $verbosity = self::NORMAL;

    /**
     * Codes for colored console output
     * Don't use a constant, to allow custom colors being set
     *
     * Example:
     *
     * \**
     *  * $this->output @see AbstractCommand::__construct
     *  *\
     * public function whateverSetYourColorCode()
     * {
     *     $this->output['custom_color_blue'] = '0;34'
     *     $this->output->writeln('<custom_color_blue>This is a blue string.</custom_color_blue> Well done!');
     * }
     */
    public $tags = [
        // Info output is colored green
        'info' => '0;32',
        // Debug output is colored yellow
        'debug' => '1;33',
        // Error output is colored red
        'error' => '0;31'
    ];

    /**
     * Output a message to the console
     * The command will highlight all registered tags with their mapped colors
     * @see Output::tags
     * @see Output::parseMessage
     */
    public function writeln(string $message = '', $verbosity = self::NORMAL): self
    {
        if ($this->verbosityDisallowsOutput($verbosity)) {
            // The message should not be outputted in the current verbosity level
            return $this;
        }

        // Replace tags, such as <info></info> with the mapped color attribute in $this->tags
        $message = $this->dissect($message);

        echo $message . PHP_EOL;

        return $this;
    }

    /**
     * Wraps the message in <error></error> tags
     * Verbosity defaults to QUIET
     */
    public function error(string $message = '', $verbosity = self::QUIET): self
    {
        return $this->writeln("<error>$message</error>", $verbosity);
    }

    /**
     * Wraps the message in <info></info> tags
     */
    public function info(string $message = '', $verbosity = self::NORMAL): self
    {
        return $this->writeln("<info>$message</info>", $verbosity);
    }

    /**
     * Wraps the message in <debug></debug>
     * Verbosity defaults to DEBUG
     */
    public function debug(string $message = '', $verbosity = self::DEBUG): self
    {
        return $this->writeln("<debug>$message</debug>", $verbosity);
    }

    /**
     * Outputs PHP_EOL respectively line breaks to the CLI
     */
    public function nl($count = 1): self
    {
        for ($i = 0; $i < $count; $i++) {
            echo PHP_EOL;
        }

        return $this;
    }

    /**
     * Check whether to display the current output or not
     */
    private function verbosityDisallowsOutput(int $verbosity): bool
    {
        if ($this->verbosity >= $verbosity) {
            // Output
            return false;
        }
        // Suppress message
        return true;
    }

    /**
     * Dissect / replace the lags like <info> with the related / mapped color string
     * I don't give a fuck about mac os here
     */
    private function dissect(string $str): string
    {
        if (strtoupper(PHP_OS) === 'LINUX') {
            // Insert the mapped colors
            $str = $this->parseMessage($str);
        } else {
            // Unfortunately, on windows we can't display colored output, so we will simply remove all the tags
            foreach ($this->tags as $tag => $color) {
                $str = str_replace("<$tag>", '', $str);
                $str = str_replace("</$tag>", '', $str);
            }
        }

        return $str;
    }

    private function parseMessage(string $str)
    {
        $closingAppendage = '_closing';
        $closingAppendageLength = strlen($closingAppendage);
        $offset = 0;
        $hierarchy = [];

        while ($offset < strlen($str)) {
            $matches = [];
            $next = [
                'tag' => '',
                'position' => null
            ];

            // Get the positions of the next color tags
            foreach ($this->tags as $tag => $color) {
                // Opening tag
                $pos = strpos($str, "<$tag>", $offset);
                if ($pos !== false) {
                    $matches[$tag] = $pos;
                }

                // Closing tag
                $pos = strpos($str, "</$tag>", $offset);
                if ($pos !== false) {
                    $matches[$tag . $closingAppendage] = $pos;
                }
            }

            if (!$matches) {
                break;
            }

            // Get the nearest / next tag
            foreach ($matches as $tag => $pos) {
                if ($next['position'] > $pos || $next['position'] === null) {
                    $next['position'] = $pos;
                    $next['tag'] = $tag;

                    $tagLength = strlen($tag);
                    if ($tagLength >= $closingAppendageLength && substr($tag, $tagLength - $closingAppendageLength) === $closingAppendage) {
                        // It's a closing tag
                        $isClosing = true;
                    } else {
                        // It's an opening tag
                        $isClosing = false;
                    }
                }
            }

            if (!$isClosing) {
                // Only add opening tags to hierarchy
                $hierarchy[] = $next['tag'];
            }

            if ($next['tag']) {
                $closingAppendagePos = strlen($next['tag']) - $closingAppendageLength;
                if (substr($next['tag'], $closingAppendagePos) === $closingAppendage) {
                    // It's a closing tag we need to process
                    $tag = substr($next['tag'], 0, $closingAppendagePos);

                    if ($hierarchy) {
                        // Need to pop twice, because I don't want the value of the most recent opening tag, i want the preceding of the most recent tag
                        $previous = array_pop($hierarchy);

                        if ($tag !== $previous) {
                            // Opening and closing tags do not fit each other
                            echo PHP_EOL . PHP_EOL . 'ERROR: Incorrect closing tag </' . $tag . '> for previously opened <' . $previous . '>' . PHP_EOL . PHP_EOL;
                        }
                    }

                    if ($hierarchy) {
                        // Get the color that needs to be inserted, the color that was used before
                        $previous = array_pop($hierarchy);

                        if (!$previous) {
                            // Standard, no preceding unclosed tag found
                            $color = '0';
                        } else {
                            $color = $this->tags[$previous];
                        }
                    } else {
                        // Standard, from now on there's default coloring again
                        $color = '0';
                    }

                    // Remove the closing appendage
                    $colorStr = $this->getColor($color);
                    $offset = $next['position'] + strlen($colorStr);
                    $str = substr_replace(
                        $str,
                        $colorStr,
                        $next['position'],
                        strlen('</' . $tag . '>')
                    );
                } else {
                    // Opening tag
                    $colorStr = $this->getColor($this->tags[$next['tag']]);
                    $offset = $next['position'] + strlen($colorStr);
                    $str = substr_replace(
                        $str,
                        $colorStr,
                        $next['position'],
                        strlen('<' . $next['tag'] . '>')
                    );
                }
            }
        }

        return $str;
    }

    private function getColor(string $add): string
    {
        return "\033[" .  $add . "m";
    }

}
