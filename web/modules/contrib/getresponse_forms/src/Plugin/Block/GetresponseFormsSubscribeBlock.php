<?php

namespace Drupal\getresponse_forms\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Subscribe' block.
 *
 * @Block(
 *   id = "getresponse_forms_subscribe_block",
 *   admin_label = @Translation("Subscribe Block"),
 *   category = @Translation("GetResponse Forms"),
 *   module = "getresponse_forms",
 *   deriver = "Drupal\getresponse_forms\Plugin\Derivative\GetresponseFormsSubscribeBlock"
 * )
 */
class GetresponseFormsSubscribeBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $signup_id = $this->getDerivativeId();

    /* @var $signup \Drupal\getresponse_forms\Entity\GetresponseForms */
    $signup = getresponse_forms_load($signup_id);

    $form = new \Drupal\getresponse_forms\Form\GetresponseFormsPageForm();

    $form_id = 'getresponse_forms_subscribe_block_' . $signup->id . '_form';
    $form->setFormID($form_id);
    $form->setSignup($signup);

    $content = \Drupal::formBuilder()->getForm($form);

    return $content;
  }

}
