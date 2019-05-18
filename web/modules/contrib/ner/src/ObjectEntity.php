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
 * NER object.
 *
 * @package Drupal\Ner
 */
class ObjectEntity {

    /**
     * Object id
     *
     * @var int|string
     */
    protected $id;

    /**
     * Object type
     *
     * @var string
     */
    protected $type;

    /**
     * Analyzed content of current object.
     *
     * @var string|object
     */
    protected $content;

    /**
     * Map of definitions for current
     * text full || partial.
     *
     * [ definitionClassName => Definition ]
     *
     * @var DefinitionEntity[]
     */
    protected $definitionMap;

    /**
     * @return int|string
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @param int|string $id
     * @return ObjectEntity
     */
    public function setId($id) {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getType(): string {
        return $this->type;
    }

    /**
     * @param string $type
     * @return ObjectEntity
     */
    public function setType(string $type): ObjectEntity {
        $this->type = $type;
        return $this;
    }

    /**
     * @return object|string
     */
    public function getContent() {
        return $this->content;
    }

    /**
     * @param object|string $content
     * @return ObjectEntity
     */
    public function setContent($content) {
        $this->content = $content;
        return $this;
    }

    /**
     * @return DefinitionEntity[]
     */
    public function getDefinitionMap() {
        return $this->definitionMap;
    }

    /**
     * @param DefinitionEntity[] $definitionMap
     * @return ObjectEntity
     */
    public function setDefinitionMap(array $definitionMap): ObjectEntity {
        $this->definitionMap = $definitionMap;
        return $this;
    }
}
