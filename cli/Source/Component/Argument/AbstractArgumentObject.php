<?php
/**
 * @author      Roland Schilffarth <roland@schilffarth.org>
 * @license     https://www.gnu.org/licenses/gpl-3.0.de.html General Public License (GNU 3.0)
 */

namespace Source\Component\Argument;

use Source\App;
use Source\Component\Interaction\Input\AbstractInputObject;
use Source\Component\Interaction\Output\Output;

/**
 * Create an argument object, including its handlers
 *
 * Please note, that arguments are always supposed to start with double hyphens: --
 * If the argument starts with a single hyphen, it is assumed to be an alias for an valid and registered argument
 */
abstract class AbstractArgumentObject
{

    /**
     * Used for unified and consistent format of @see AbstractArgumentObject::name
     */
    const STR_CODE = 2;

    /**
     * Used for unified and consistent format for each alias of @see AbstractArgumentObject::aliases
     */
    const STR_ALIAS = 1;

    /**
     * The name / code of the argument
     */
    public $name = '';

    /**
     * The arguments description is displayed when your command is called with the parameter --help or its alias -h
     */
    public $description = '';

    /**
     * All registered aliases for the argument
     * @var string[]
     */
    public $aliases = [];

    /**
     * Specified arguments that will be excluded by the usage of this argument
     * @var string[]
     */
    public $excludes = [];

    /**
     * Arguments that are required to be present in combination with this argument
     * @var string[]
     */
    public $requires = [];

    /**
     * Boolean, whether the command is called with this argument or not
     */
    public $passed = false;

    /**
     * Instance is set in @see App::registerArguments
     *
     * @var Output $output
     */
    public $output;

    /**
     * Instance is set in @see App::registerArguments
     *
     * @var AbstractInputObject $input
     */
    public $input;

    /**
     * Contains all registered callback
     * Handlers will be called one by one, unless a priority has been defined
     * @var callable[]
     */
    private $handler = [];

    /**
     * Build a new argument
     */
    public function __construct(
        Output $output,
        AbstractInputObject $input
    ) {
        $this->output = $output;
        $this->input = $input;
    }

    /**
     * Argument is created with @see ArgumentFactory::create
     */
    public function create(string $name, string $description, string ...$aliases)
    {
        $this->name = self::trimProperty($name);
        $this->description = $description;
        foreach ($aliases as $alias) {
            $this->aliases[] = self::trimProperty($alias, self::STR_ALIAS);
        }
    }

    /**
     * Retrieve codes for argument aliases and names without applying preceding hyphens yourself
     * This method provides unified and consistent format for both aliases and argument names
     */
    public static function trimProperty(string $name, int $type = self::STR_CODE)
    {
        if ($type === self::STR_CODE) {
            $pre = '--';
        } else {
            // $type === self::STR_ALIAS
            $pre = '-';
        }

        return strncmp($name, $pre, $type) === 0 ? $name : $pre . $name;
    }

    /**
     * Initialize / build up your argument - $this->passed is set before this method is called
     * With this function you can introduce special logic for your argument, such as complex type handling
     * For an example @see Complex
     */
    abstract public function launch(array &$argv);

    /**
     * Register a function that is called whenever the argument is supplied
     * You can set up multiple handlers with priority - Handlers can be added after the argument has been initialized
     */
    public function registerHandler(callable $handler, int $priority = 0): self
    {
        if ($priority === 0) {
            $this->handler[] = $handler;
        } else {
            // Add the handler, but make sure to keep existing handlers and not override one accidentally
            if (isset($this->handler[$priority])) {
                /** @var \Source\Component\Interaction\AbstractOutput $output */
                $output = inject(Output::class);
                $output->error('Cannot initialize argument handler properly at index ' . $priority .'. The given priority has already been set.');
                $this->handler[] = $handler;
            } else {
                $this->handler[$priority] = $handler;
                ksort($this->handler);
            }
        }

        return $this;
    }

    /**
     * Set an exclude for this argument - None of the initialized exclude arguments is valid to be passed
     */
    public function excludes(string $argName): self
    {
        $this->excludes[] = self::trimProperty($argName);

        return $this;
    }

    /**
     * Set a requirement when this argument is passed
     */
    public function requires(string $argName): self
    {
        $this->requires[] = self::trimProperty($argName);

        return $this;
    }

    /**
     * Trigger / call all registered handlers
     */
    public function trigger()
    {
        foreach ($this->handler as $handle) {
            call_user_func($handle);
        }
    }

}
