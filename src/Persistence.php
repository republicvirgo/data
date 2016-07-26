<?php

// vim:ts=4:sw=4:et:fdm=marker:fdl=0

namespace atk4\data;

/**
 * Class description?
 */
class Persistence
{
    use \atk4\core\ContainerTrait {
        add as _add;
    }
    use \atk4\core\FactoryTrait;
    use \atk4\core\HookTrait;
    use \atk4\core\AppScopeTrait;

    /**
     * Connects database.
     *
     * @param string $dsn
     * @param string $user
     * @param string $password
     * @param array  $args
     *
     * @return Persistence
     */
    public static function connect($dsn, $user = null, $password = null, $args = [])
    {
        if (strpos($dsn, ':') === false) {
            throw new Exception(["Your DSN format is invalid. Must be in 'driver:host:options' format", 'dsn' => $dsn]);
        }
        $driver = explode(':', $dsn, 2)[0];

        switch (strtolower(isset($args['driver']) ?: $driver)) {
            case 'mysql':
            case 'dumper':
            case 'counter':
            case 'sqlite':
                return new Persistence_SQL($dsn, $user, $password, $args);
            default:
                throw new Exception([
                    'Unable to determine pesistence driver from DSN',
                    'dsn' => $dsn,
                ]);
        }
    }

    /**
     * Associate model with the data driver.
     *
     * @param Model|string $m        Model which will use this persistence
     * @param array        $defaults Properties
     *
     * @return Model
     */
    public function add($m, $defaults = [])
    {
        if (isset($defaults[0])) {
            $m->table = $defaults[0];
            unset($defaults[0]);
        }

        if (!is_object($m)) {
            $m = $this->factory($this->normalizeClassName($m), $defaults);
        }

        if ($m->persistence) {
            throw new Exception([
                'Model already has conditions or is related to persistence',
            ]);
        }

        $m->setDefaults($defaults);
        $m->persistence = $this;
        $m->persistence_data = [];
        $this->initPersistence($m);
        $m = $this->_add($m, $defaults);

        $this->hook('afterAdd', [$m]);

        return $m;
    }

    /**
     * Extend this method to enhance model to work with your persistence. Here
     * you can define additional methods or store additional data. This method
     * is executed before model's init().
     *
     * @param Model $m
     */
    protected function initPersistence(Model $m)
    {
    }
}