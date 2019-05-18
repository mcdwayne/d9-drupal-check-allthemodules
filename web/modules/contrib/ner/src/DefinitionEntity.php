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
 * NER definition.
 *
 * @package Drupal\ner
 */
class DefinitionEntity {

    /**
     * Definition class name + extraction algorithm.
     *
     * @var string
     */
    protected $longId;

    /**
     * Definition class name.
     *
     * @var string
     */
    protected $sortId;

    /**
     * Extraction algorithm.
     *
     * @var string
     */
    protected $extractionAlgorithm;

    /**
     * Text content when extraction
     * algorithms works.
     *
     * @var string
     */
    protected $content;

    /**
     * @var PropertyDefinitionEntity[]
     */
    protected $propertyDefinitionMap;

    /**
     * @return string
     */
    public function getLongId(): string {
        return $this->longId;
    }

    /**
     * @param string $longId
     * @return DefinitionEntity
     */
    public function setLongId(string $longId): DefinitionEntity {
        $this->longId = $longId;
        return $this;
    }

    /**
     * @return string
     */
    public function getSortId(): string {
        return $this->sortId;
    }

    /**
     * @param string $sortId
     * @return DefinitionEntity
     */
    public function setSortId(string $sortId): DefinitionEntity {
        $this->sortId = $sortId;
        return $this;
    }

    /**
     * @return string
     */
    public function getContent(): string {
        return $this->content;
    }

    /**
     * @param string $content
     * @return DefinitionEntity
     */
    public function setContent(string $content): DefinitionEntity {
        $this->content = $content;
        return $this;
    }

    /**
     * @return string
     */
    public function getExtractionAlgorithm(): string {
        return $this->extractionAlgorithm;
    }

    /**
     * @param string $extractionAlgorithm
     * @return DefinitionEntity
     */
    public function setExtractionAlgorithm(string $extractionAlgorithm): DefinitionEntity {
        $this->extractionAlgorithm = $extractionAlgorithm;
        return $this;
    }

    /**
     * @return PropertyDefinitionEntity[]
     */
    public function getPropertyDefinitionMap(): array {
        return $this->propertyDefinitionMap;
    }

    /**
     * @param PropertyDefinitionEntity[] $propertyDefinitionMap
     * @return DefinitionEntity
     */
    public function setPropertyDefinitionMap(array $propertyDefinitionMap): DefinitionEntity {
        $this->propertyDefinitionMap = $propertyDefinitionMap;
        return $this;
    }
}