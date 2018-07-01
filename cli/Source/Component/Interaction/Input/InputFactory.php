<?php
/**
 * @author      Roland Schilffarth <roland@schilffarth.org>
 * @license     https://www.gnu.org/licenses/gpl-3.0.de.html General Public License (GNU 3.0)
 */

namespace Source\Component\Interaction\Input;

class InputFactory
{

    const INPUT_LABELED = Labeled::class;

    public function create(string $type, ...$args): AbstractInputObject
    {
        $obj = inject($type, true);
        $obj->create(...$args);

        return $obj;
    }

}
