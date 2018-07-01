<?php
/**
 * @author      Roland Schilffarth <roland@schilffarth.org>
 * @license     https://www.gnu.org/licenses/gpl-3.0.de.html General Public License (GNU 3.0)
 */

namespace Source\Component\Interaction\Input;

use Source\Objects\ObjectManager;

class InputFactory
{

    const INPUT_LABELED = Labeled::class;

    private $objectManager;

    public function __construct(
        ObjectManager $objectManager
    ) {
        $this->objectManager = $objectManager;
    }

    public function create(string $type, ...$args): AbstractInputObject
    {
        /** @var AbstractInputObject $object */
        $object = $this->objectManager->createObject($type);
        $object->create(...$args);

        return $object;
    }

}
