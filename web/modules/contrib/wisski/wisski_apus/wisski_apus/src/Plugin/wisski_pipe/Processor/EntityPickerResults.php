<?php

/**
 * @file
 * Contains \Drupal\wisski_pipe\Plugin\wisski_pipe\Processor\Noop.
 */

namespace Drupal\wisski_apus\Plugin\wisski_pipe\Processor;

use Drupal\wisski_pipe\ProcessorInterface;
use Drupal\wisski_pipe\ProcessorBase;
use Drupal\wisski_salz\AdapterHelper;


/**
 * @Processor(
 *   id = "entity_picker_results",
 *   label = @Translation("Entity Picker Results"),
 *   description = @Translation("Transforms the annotations of a text analysis and makes a list of entities to be displayed."),
 *   tags = { "post-processing", "text" }
 * )
 */
class EntityPickerResults extends ProcessorBase {
  
  
  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  
  /**
   * {@inheritdoc}
   */
  public function doRun() {

    if (!isset($this->data['annos'])) {
      $this->data = array();
    } else {
      
      $entities = array();

      foreach ($this->data['annos'] as $anno) {
        
        if ($anno['uri']) {
          
          if (preg_match('!node/(\d+)$!u', $anno['uri'], $m)) {
            $label = entity_load('node', $m[1])->label();
          } else {
            $entity_id = AdapterHelper::getDrupalIdForUri($anno['uri']);
            $entity = entity_load('wisski_individual', $entity_id);
            $label =$entity->label();
          }

          $entity = array(
            'uri' => $anno['uri'],
            'label' => $label,
          );
          $entities[] = $entity;
        }
        
      }
      
      $this->data = $entities;

    }

  }

}
