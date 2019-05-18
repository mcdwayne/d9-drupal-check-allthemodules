<?php

/**
 * @file
 * Definition of Drupal\maestro\Plugin\views\field\MaestroEngineUserWhoCompleted
 */

namespace Drupal\maestro\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\maestro\Engine\MaestroEngine;
use Drupal\maestro\Utility\TaskHandler;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\Core\Url;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\UrlHelper;

/**
 * Field handler to display the entity label for the entity POINTED TO from the entity identifiers 
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("maestro_entity_identifiers_label")
 */
class MaestroEngineEntityIdentifierEntityLabel extends FieldPluginBase {

  /**
   * @{inheritdoc}
   */
  public function query() {
    // no Query to be done.
  }

  /**
   * Define the available options
   * @return array
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['link_to_entity'] = ['default' => 0];
    return $options;
  }

  /**
   * Provide the options form.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['link_to_entity'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Provide a link to the entity?'),
      '#description' => $this->t('When checked, the output in the view will show a link to the entity.'),
      '#default_value' => isset($this->options['link_to_entity']) ? $this->options['link_to_entity'] : 0 ,
    );
    
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * @{inheritdoc}
   */
  public function render(ResultRow $values) {
    $result = '';
    $urlToEntity = '';
    $item = $values->_entity;
    //this will ONLY work for maestro entity identifiers
    if ($item->getEntityTypeId() == 'maestro_entity_identifiers') {
      $entity_manager = \Drupal::entityTypeManager();
      $entity = $entity_manager->getStorage($item->entity_type->getString())->load($item->entity_id->getString());
      if(isset($entity)) {
        $result = $entity->label();
        $urlToEntity = $entity->access('view') ? $entity->toUrl('canonical', ['query' => ['maestro' => 1]])->toString() : '';
      }
      else {
        $result = '';
      }
    }
    else {
      return '';
    }
    
    if($this->options['link_to_entity'] && $result && $urlToEntity) {
      return ['#markup' => '<a href="' . $urlToEntity . '" class="maestro_who_completed_field">' . $result . '</a>'];
    }
    else {
      return $result;
    }
  }
}