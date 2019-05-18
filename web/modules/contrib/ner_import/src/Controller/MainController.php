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

namespace Drupal\ner_import\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;

/**
 * Default controller of module
 *
 * @package Drupal\ner
 */
class MainController extends ControllerBase {

    /**
     * NER importer home page.
     *
     * @return array
     */
    public function setup() {

        return [
            '#theme' => 'setup_page',
            '#links' => [
                (string)$this->t('Import structure') => Url::fromRoute('ner_import.import_structure'),
                (string)$this->t('Import content') => Url::fromRoute('ner_import.import_content')
            ]
        ];
    }

    /**
     * Processed data when client execute
     * any structure import process.
     *
     * @return array
     */
    public function structureProcessed(){

        return [
            '#theme' => 'processed_structure_page',
            '#compressed_module_url' => \Drupal::request()->query->get('compressed_module_url'),
            '#property_field_map' => \Drupal::request()->query->get('property_field_map'),
            '#content_type_id' => \Drupal::request()->query->get('content_type_id')
        ];
    }

    /**
     * Processed data when client execute
     * any data import process.
     *
     * @return array
     */
    public function contentProcessed(){

        $nodeIdList = explode(',', \Drupal::request()->query->get('node_id_list'));

        $nodeList = [];
        foreach ($nodeIdList as $nodeId)
            $nodeList[] = Node::load($nodeId);

        return [
            '#theme' => 'processed_content_page',
            '#node_list' => $nodeList
        ];
    }
}