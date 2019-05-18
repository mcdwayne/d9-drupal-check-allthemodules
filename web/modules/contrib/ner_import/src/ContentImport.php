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


use Drupal\ner\ObjectEntity;
use Drupal\node\Entity\Node;

class ContentImport {

    /**
     * Content type id.
     *
     * @var string
     */
    protected $contentTypeId;

    /**
     * Array map where:
     *  - key: PropertyEntity=>property
     *  - value: ContentType => field => field_name
     *
     * @var string[] [string=>string, ...]
     */
    protected $propertyToFieldMap;

    /**
     * ContentImport constructor.
     */
    public function __construct() {

    }

    /**
     * Import content by ObjectEntity list.
     *
     * Firstly you need set contentTypeId and
     * propertyToFieldMap.
     *
     * @param ObjectEntity[] $objectEntityList
     * @return int[] Node id list.
     */
    public function byObjectEntityList($objectEntityList){

        $nIdList = [];
        foreach ($objectEntityList as $objectEntity)
            $nIdList[] = $this->byObjectEntity($objectEntity);

        return $nIdList;
    }

    /**
     * Import content by ObjectEntity.
     *
     * Firstly you need set contentTypeId and
     * propertyToFieldMap.
     *
     * @param ObjectEntity $objectEntity
     * @return int Node id.
     */
    public function byObjectEntity($objectEntity){

        $nodeData = [];
        $nodeData['field_map'] = $this->fieldMapByObjectEntity($objectEntity);

        return $this->createNode($nodeData);
    }

    /**
     * @param [] $nodeData
     * @return int Node id.
     */
    protected function createNode($nodeData){

        $n = Node::create(array(
            'type' => $this->contentTypeId,
            'langcode' => isset($nodeData['langcode']) ? $nodeData['langcode'] : 'en',
            'status' => isset($nodeData['status']) ? $nodeData['status'] : 1,
        ));

        if(isset($nodeData['field_map']))
            foreach ($nodeData['field_map'] as $fieldKey => $fieldValue)
                $n->{$fieldKey}->setValue($fieldValue);

        $n->save();
        return $n->id();
    }

    /**
     * @param ObjectEntity $objectEntity
     * @return array
     */
    protected function fieldMapByObjectEntity($objectEntity){

        $fieldMap = [];
        foreach ($objectEntity->getDefinitionMap() as $definitionEntity) {
            foreach ($definitionEntity->getPropertyDefinitionMap() as $property) {

                if(!isset($this->propertyToFieldMap[$property->getProperty()]))
                    continue;

                $propertyKey = $this->propertyToFieldMap[$property->getProperty()];

                if (!isset($fieldMap[$propertyKey])) {

                    $fieldMap[$propertyKey] = $property->getValue();
                } elseif (is_array($fieldMap[$propertyKey])) {

                    $fieldMap[$propertyKey][] = $property->getValue();
                } else {

                    $fieldMap[$propertyKey] = [
                        $fieldMap[$propertyKey],
                        $property->getValue()
                    ];
                }
            }
        }

        return $fieldMap;
    }

    /**
     * @param string $contentTypeId node content for
     * current content import work.
     * @return string
     */
    public function setContentTypeId($contentTypeId){

        $this->contentTypeId = $contentTypeId;
        return $this;
    }

    /**
     * @param array $propertyToFieldMap map with PropertyEntity => property
     * - ContentType field => field_name relation during import process.
     * @return ContentImport
     */
    public function setPropertyToFieldMap($propertyToFieldMap){

        $this->propertyToFieldMap = $propertyToFieldMap;
        return $this;
    }
}