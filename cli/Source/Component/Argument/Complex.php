<?php
/**
 * @author      Roland Schilffarth <roland@schilffarth.org>
 * @license     https://www.gnu.org/licenses/gpl-3.0.de.html General Public License (GNU 3.0)
 */

namespace Source\Component\Argument;

/**
 * Complex arguments represent options that need to take a value, initialized by the users input
 *
 * todo Possible formats for passing an argument with TYPE_VALUE
 * --argument value
 * --argument value
 * --arg "value"
 * -alias value
 *
 * Example usage:
 * --filename   -f      If the user passes `--filename example.txt` this arguments value will be set to example.txt
 * --locations  -l      Passing `--`
 */
class Complex extends AbstractArgumentObject
{

    /**
     * The command won't run if argument is required
     */
    public $required = false;

    /**
     * If the argument is passed, this variable holds the initialized value
     */
    public $value = '';

    public function launch(array &$argv)
    {
        if ($this->required && !$this->passed) {
            $this->output->error(sprintf('Argument %s is required but not specified!', $this->name));
            exit;
        }

        if (!$this->passed) {
            return;
        }

        $value = array_shift($argv);
        if ($value === null) {
            // todo Create an InputObject that will let the user specify the arguments value

            $this->output->error(sprintf('No value specified for argument %s', $this->name));
            exit;
        }

        $this->value = $value;
    }

}
