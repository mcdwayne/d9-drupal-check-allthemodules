<?php

namespace Drupal\wisski_core\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow; 
use Drupal\Core\Url;
use Drupal\views\Plugin\views\field\Url as Urlfield;

/**
 * Default implementation of the base field plugin.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("wisski_title")
 */
class WisskiTitle extends Urlfield {
  
  /**
   * {@inheritdoc}
   */ 
  public function render(ResultRow $values) {
    
    $value = $this->getValue($values);
#    dpm(serialize($values), "vals");
    $entity = $values->_entity;
    if(!empty($entity))
      $eid = $entity->id();
    else {
      if(!empty($values->eid)) {
        $eid = $values->eid;
      } else {
        $eid = NULL;
      }
    }
//   dpm(Url::fromRoute('entity.wisski_individual.canonical', ['wisski_individual' => $eid]), "url");
#    dpm(serialize($eid), "eid");
//    dpm(serialize($values), "val");
    if (is_array($value)) {
      $return = [];

      foreach ($value as $v) {
        
        // in case of a disamb-array, go to the value.
        if(is_array($v) && isset($v["value"]))
          $return[] = $this->sanitizeValue($v["value"]);
        else 	         
          $return[] = $this->sanitizeValue($v);
      }
      return join(', ', $return);
    }
    else {
      if (!empty($this->options['display_as_link']) && !empty($eid)) {
        return \Drupal::l($this->sanitizeValue($value), Url::fromRoute('entity.wisski_individual.canonical', ['wisski_individual' => $eid])); //"<a href='" . Url::fromRoute('entity.wisski_individual.canonical', ['wisski_individual' => $entity]) . "'>" . $this->sanitizeValue($value) . "</a>";
      } else {
        return $this->sanitizeValue($value, 'url');
      }
    }
  }

}   

