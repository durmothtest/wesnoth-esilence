<?php
/**
 * @author      Roland Schilffarth <roland@schilffarth.org>
 * @license     https://www.gnu.org/licenses/gpl-3.0.de.html General Public License (GNU 3.0)
 */

namespace Source\Component\Argument;

use Source\Objects\ObjectManager;

class ArgumentFactory
{

    const ARGUMENT_SIMPLE = Simple::class;
    const ARGUMENT_COMPLEX = Complex::class;

    private $objectManager;

    public function __construct(
        ObjectManager $objectManager
    ) {
        $this->objectManager = $objectManager;
    }

    public function create(string $type, ...$args): AbstractArgumentObject
    {
        /** @var AbstractArgumentObject $object */
        $object = $this->objectManager->createObject($type);
        $object->create(...$args);

        return $object;
    }

}
