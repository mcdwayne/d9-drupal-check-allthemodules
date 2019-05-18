<?php

namespace Drupal\getresponse_forms\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;

/**
 * Provides block plugin definitions for GetResponse Forms blocks.
 *
 * @see \Drupal\getresponse_forms\Plugin\Block\GetresponseFormsSubscribeBlock
 */
class GetresponseFormsSubscribeBlock extends DeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $signups = getresponse_forms_load_multiple();

    /* @var $signup \Drupal\getresponse_forms\Entity\GetresponseForms */
    foreach ($signups as $signup) {
      if (intval($signup->mode) == GETRESPONSE_FORMS_BLOCK || intval($signup->mode) == GETRESPONSE_FORMS_BOTH) {

        $this->derivatives[$signup->id] = $base_plugin_definition;
        $this->derivatives[$signup->id]['admin_label'] = t('GetResponse Form: @name', array('@name' => $signup->label()));
      }
    }

    return $this->derivatives;
  }

}
