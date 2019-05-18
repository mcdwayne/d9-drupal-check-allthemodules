<?php

namespace CleverReach\BusinessLogic\Entity;

/**
 * Class Tag
 *
 * @package CleverReach\BusinessLogic\Entity
 */
class Tag extends AbstractTag
{
    /**
     * Tag constructor.
     *
     * @param string $name Tag name.
     * @param string $type Tag type.
     */
    public function __construct($name, $type)
    {
        parent::__construct($name, $type);
    }
}
