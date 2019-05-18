<?php

namespace Drupal\asf\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\asf\AsfSchema;

/**
 * Plugin implementation of the 'AsfSchemaFormatter' formatter.
 *
 * @FieldFormatter(
 *   id = "AsfSchemaFormatter",
 *   module = "asf",
 *   label = @Translation("Show Advanced Publication Schema"),
 *   field_types = {
 *     "asf"
 *   }
 * )
 */
class AsfSchemaFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    $entity = $items->getEntity();
    $def = $this->fieldDefinition;
    $fieldName = $def->getName();
    $actions = $this->fetchPendingActions($entity->id(), $fieldName);
    $elements = $this->formatActions($actions);
    return $elements;
  }

  /**
   * Fetch pending Actions
   * @param $id
   * @param $fieldName
   */
  function fetchPendingActions($id, $fieldName){
    $options = array(
      'eid' => $id,
      'status' => ASF_STATUS_PENDING,
      'fid' => $fieldName,
    );
    return AsfSchema::selectActions($options);
  }


  /**
   * Format all actions
   *
   * @param $actions
   * @return array
   */
  function formatActions($actions){
    $elements = array();
    $table = array(
      '#type' => 'table',
      '#header' => array(t('Timestamp') , t('Action')),
      '#empty' => t('No (un)publication planned'),
    );
    foreach($actions as $key => $action){
//      $table[$key]['field'] = array(
//        '#plain_text' => $action->fid,
//      );
      $table[$key]['time'] = array(
        '#plain_text' => date('Y-m-d H:i',$action->time),
      );
      $table[$key]['action'] = array(
        '#plain_text' => $action->action,
      );

    }
    $elements[] = $table;
    return $elements;
  }



}
