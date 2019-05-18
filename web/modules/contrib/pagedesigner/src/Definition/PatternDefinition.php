<?php

namespace Drupal\pagedesigner\Definition;

use Drupal\ui_patterns\Definition\PatternDefinition as Orig;

/**
 * Class PatternDefinition.
 *
 * @package Drupal\pagedesigner\Definition
 */
class PatternDefinition extends Orig
{
    /**
     * Getter.
     *
     * @return mixed
     *   Property value.
     */
    public function getId()
    {
        return $this->definition['id'];
    }

    /**
     * Setter.
     *
     * @param mixed $label
     *   Property value.
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->definition['id'] = $id;
        return $this;
    }
}
