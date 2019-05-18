<?php
/**
 * DRUPAL 8 NER.
 * Copyright (C) 2017. Tarik Curto <centro.tarik@live.com>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 */

namespace Drupal\ner;

/**
 * NER property definition.
 *
 * @package Drupal\ner
 */
class PropertyDefinitionEntity {

    /**
     * Property key of definition.
     *
     * @var string
     */
    protected $property;

    /**
     * Value of definition.
     *
     * @var string
     */
    protected $value;

    /**
     * @return string
     */
    public function getProperty(): string {
        return $this->property;
    }

    /**
     * @param string $property
     * @return PropertyDefinitionEntity
     */
    public function setProperty(string $property): PropertyDefinitionEntity {
        $this->property = $property;
        return $this;
    }

    /**
     * @return string
     */
    public function getValue(): string {
        return $this->value;
    }

    /**
     * @param string $value
     * @return PropertyDefinitionEntity
     */
    public function setValue(string $value): PropertyDefinitionEntity {
        $this->value = $value;
        return $this;
    }


}