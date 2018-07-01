<?php
/**
 * @author      Roland Schilffarth <roland@schilffarth.org>
 * @license     https://www.gnu.org/licenses/gpl-3.0.de.html General Public License (GNU 3.0)
 */

namespace Command;

use Source\Command\AbstractCommand;
use Source\Component\Argument\ArgumentFactory;
use Source\Component\Interaction\Input\InputFactory;

class Example extends AbstractCommand
{

    public $command = 'example-command';

    public $help = 'The command is an example of how to add your own command. Just check out its source at ' . __FILE__;

    public function init()
    {
        $test = $this->argumentFactory->create(
            ArgumentFactory::ARGUMENT_COMPLEX,
            'test',
            'Argument testing with a test argument. This is a test description.',
            't'
        );
        $this->setArgument($test);
    }

    public function run()
    {
        $this->error('test error');
        $this->debug('test debug');
        $this->info('test info');

        $this->writeln('Testing input object');
        $this->inputFactory
            ->create(InputFactory::INPUT_LABELED, 'test label')
            ->request();

        // Success! Return false on failure
        return true;
    }

}
