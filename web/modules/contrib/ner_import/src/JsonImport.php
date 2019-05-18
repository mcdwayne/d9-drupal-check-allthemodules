<?php
/**
 * DRUPAL 8 NER importer.
 * Copyright (C) 2017. Tarik Curto <centro.tarik@live.com>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 */

namespace Drupal\ner_import;

use Drupal\ner\DefinitionEntity;
use Drupal\ner\ObjectEntity;
use Drupal\ner\PropertyDefinitionEntity;

/**
 * NER import extension for import/transform NER
 * sources in JSON format.
 *
 * @package Drupal\ner_import
 */
class JsonImport {

    /**
     * JSON object with client import data.
     *
     * @var object|array
     */
    protected $jsonSource;

    /**
     * JSON import source list to
     * ObjectEntity list.
     *
     * @see ObjectEntity For see valid JSON structure.
     * @param \stdClass|\stdClass[] $json
     * @param boolean $buildSubObject Check if $json has subObject.
     * @return ObjectEntity[]
     */
    public function objectEntityListByJson($json, $buildSubObject = true) {
        $objectEntityList = [];
        foreach ($json as $stdClass) {
            $objectEntity = $this->objectEntityByJson($stdClass, $buildSubObject);
            $objectEntityKey = $buildSubObject ? $objectEntity->getType() . '/' . $objectEntity->getId() : $objectEntity->getContent();
            $objectEntityList[$objectEntityKey] = $objectEntity;
        }

        return $objectEntityList;
    }

    /**
     * JSON import source object to
     * ObjectEntity.
     *
     * @param \stdClass $json
     * @return ObjectEntity;
     */
    public function objectEntityByJson(\stdClass $json): ObjectEntity {

        $objectEntity = new ObjectEntity();

        if (isset($json->id))
            $objectEntity->setId($json->id);

        if (isset($json->type))
            $objectEntity->setType($json->type);

        if (isset($json->content))
            $objectEntity->setContent($json->content);

        if (isset($json->definitionMap)) {
            $definitionEntityMap = $this->definitionEntityListByJsonDefinitionList($json->definitionMap);
            $objectEntity->setDefinitionMap($definitionEntityMap);
        }

        return $objectEntity;
    }

    /**
     * JSON import source definition list to
     * DefinitionEntity list.
     *
     * @param \stdClass|\stdClass[] $jsonDefinitionList
     * @return DefinitionEntity[]
     */
    protected function definitionEntityListByJsonDefinitionList($jsonDefinitionList) {

        $definitionEntityList = [];
        foreach ($jsonDefinitionList as $jsonDefinition) {
            $definitionEntity = $this->definitionEntityByJsonDefinition($jsonDefinition);
            $definitionEntityKey = $definitionEntity->getLongId();
            $definitionEntityList[$definitionEntityKey] = $definitionEntity;
        }

        return $definitionEntityList;
    }

    /**
     * JSON import source definition to
     * DefinitionEntity.
     *
     * @param \stdClass $jsonDefinition
     * @return DefinitionEntity
     */
    protected function definitionEntityByJsonDefinition(\stdClass $jsonDefinition): DefinitionEntity {

        $definitionEntity = new DefinitionEntity();

        if (isset($jsonDefinition->longId))
            $definitionEntity->setLongId($jsonDefinition->longId);

        if (isset($jsonDefinition->sortId))
            $definitionEntity->setSortId($jsonDefinition->sortId);

        if (isset($jsonDefinition->content))
            $definitionEntity->setContent($jsonDefinition->content);

        if (isset($jsonDefinition->extractionAlgorithm))
            $definitionEntity->setExtractionAlgorithm($jsonDefinition->extractionAlgorithm);

        if (isset($jsonDefinition->propertyDefinitionMap)) {
            $propertyDefinitionEntityList = $this->propertyDefinitionEntityListByJsonPropertyDefinitionList($jsonDefinition->propertyDefinitionMap);
            $definitionEntity->setPropertyDefinitionMap($propertyDefinitionEntityList);
        }

        return $definitionEntity;
    }

    /**
     * JSON import source property definition list to
     * PropertyDefinitionEntity list.
     *
     * @param \stdClass|\stdClass[] $jsonPropertyDefinitionList
     * @return PropertyDefinitionEntity[]
     */
    protected function propertyDefinitionEntityListByJsonPropertyDefinitionList($jsonPropertyDefinitionList) {

        $propertyDefinitionEntityList = [];
        foreach ($jsonPropertyDefinitionList as $jsonPropertyDefinition) {
            $propertyDefinitionEntity = $this->propertyDefinitionEntityByJsonPropertyDefinition($jsonPropertyDefinition);
            $propertyDefinitionEntityList[$propertyDefinitionEntity->getProperty()] = $propertyDefinitionEntity;
        }

        return $propertyDefinitionEntityList;
    }

    /**
     * JSON import source property definition to
     * PropertyDefinitionEntity.
     *
     * @param \stdClass $jsonPropertyDefinition
     * @return PropertyDefinitionEntity
     */
    protected function propertyDefinitionEntityByJsonPropertyDefinition(\stdClass $jsonPropertyDefinition): PropertyDefinitionEntity {

        $propertyDefinitionEntity = new PropertyDefinitionEntity();

        if (isset($jsonPropertyDefinition->property))
            $propertyDefinitionEntity->setProperty($jsonPropertyDefinition->property);

        if (isset($jsonPropertyDefinition->value))
            $propertyDefinitionEntity->setValue($jsonPropertyDefinition->value);

        return $propertyDefinitionEntity;
    }
}