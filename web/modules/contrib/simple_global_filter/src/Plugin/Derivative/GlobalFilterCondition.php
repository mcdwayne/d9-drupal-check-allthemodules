<?php

namespace Drupal\simple_global_filter\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
/**
 * Description of GlobalFilterBlock
 *
 * @author alberto
 */
class GlobalFilterCondition extends DeriverBase {

  use StringTranslationTrait;
  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $global_filters = \Drupal::entityTypeManager()->getStorage('global_filter')->loadMultiple();
    foreach($global_filters as $id => $global_filter) {
      $this->derivatives[$id] = $base_plugin_definition;
      $this->derivatives[$id]['label'] = $this->t('%vocabulary (Global Filter)', ['%vocabulary' => ucwords($global_filter->getVocabulary())]);
      $this->derivatives[$id]['id'] = $global_filter->id();
    }
    return $this->derivatives;
  }

}
