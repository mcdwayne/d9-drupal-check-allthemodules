<?php

namespace Drupal\sendinblue\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;

/**
 * Provides block plugin definitions for sendinblue blocks.
 *
 * @see \Drupal\sendinblue\Plugin\Block\SendinblueBlock
 */
class SendinblueBlock extends DeriverBase {

  /**
   * Provide multiple blocks for sendinblue signup forms.
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $entity_manager = \Drupal::entityTypeManager();
    $signups = $entity_manager->getStorage(SENDINBLUE_SIGNUP_ENTITY)
      ->loadMultiple();

    foreach ($signups as $signup) {
      if (intval($signup->mode->value) == SENDINBLUE_SIGNUP_BLOCK || intval($signup->mode->value) == SENDINBLUE_SIGNUP_BOTH) {
        $this->derivatives[$signup->mcsId->value] = $base_plugin_definition;
        $this->derivatives[$signup->mcsId->value]['admin_label'] = t('SendinBlue Subscription Form: @name', ['@name' => $signup->name->value]);
        $this->derivatives[$signup->mcsId->value]['mcsId'] = $signup->mcsId->value;
      }
    }

    return $this->derivatives;
  }

}
