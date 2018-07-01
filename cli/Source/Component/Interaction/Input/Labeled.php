<?php
/**
 * @author      Roland Schilffarth <roland@schilffarth.org>
 * @license     https://www.gnu.org/licenses/gpl-3.0.de.html General Public License (GNU 3.0)
 */

namespace Source\Component\Interaction\Input;

use Source\Component\Interaction\Output\Output;

class Labeled extends AbstractInputObject
{

    /**
     * The label that will be displayed the next time a user input is requested with @see AbstractInputObject::nextLine
     */
    protected $label = '';

    protected $labelVerbosity = Output::QUIET;

    public function create(string $label = '', $labelVerbosity = Output::QUIET)
    {
        $this->label = $label;
        $this->labelVerbosity = $labelVerbosity;
    }

    public function request()
    {
        if (!$this->output->verbosityDisallowsOutput($this->labelVerbosity)) {
            $this->output->comment($this->label);
        }

        parent::request();
    }

}
