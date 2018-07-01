<?php
/**
 * @author      Roland Schilffarth <roland@schilffarth.org>
 * @license     https://www.gnu.org/licenses/gpl-3.0.de.html General Public License (GNU 3.0)
 */

namespace Source\Component\Argument;

class ArgumentFactory
{

    const ARGUMENT_SIMPLE = Simple::class;
    const ARGUMENT_COMPLEX = Complex::class;

    public function create(string $type, ...$args): AbstractArgumentObject
    {
        $obj = inject($type, true);
        $obj->create(...$args);

        return $obj;
    }

}
