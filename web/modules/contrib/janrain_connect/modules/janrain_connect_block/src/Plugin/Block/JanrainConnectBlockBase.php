<?php

namespace Drupal\janrain_connect_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a Janrain block.
 *
 * @Block(
 *   id = "janrain_connect_block",
 *   admin_label = @Translation("Janrain Connect block"),
 *   category = @Translation("Janrain Connect Block"),
 *   deriver = "Drupal\janrain_connect_block\Plugin\Derivative\JanrainConnectBlock"
 * )
 */
class JanrainConnectBlockBase extends BlockBase {

  /**
   * Build the content for Janrain block.
   */
  public function build() {

    $form_id = $this->getDerivativeId();

    // @codingStandardsIgnoreLine
    $form = \Drupal::service('class_resolver')->getInstanceFromDefinition('Drupal\janrain_connect_ui\Form\JanrainConnectUiForm');
    $form->formId = mb_strtolower($form_id);
    // Get Form for render form. @codingStandardsIgnoreLine
    return \Drupal::formBuilder()->getForm($form, $form_id);
  }

}
