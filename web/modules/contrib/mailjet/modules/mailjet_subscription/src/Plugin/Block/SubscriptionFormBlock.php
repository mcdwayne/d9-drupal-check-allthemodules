<?php

namespace Drupal\mailjet_subscription\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Subscribe' block.
 *
 * @Block(
 *   id = "mailjet_signup_subscribe_block",
 *   admin_label = @Translation("Subscribe Block"),
 *   category = @Translation("Mailjet Signup"),
 *   module = "mailjet_subscription",
 *   deriver =
 *   "Drupal\mailjet_subscription\Plugin\Derivative\SubscriptionDerivativeBlock"
 * )
 */
class SubscriptionFormBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $signup_id = $this->getDerivativeId();
    $content = NULL;

    /* @var $signup \Drupal\mailjet_subscription\Entity\SubscriptionForm */

    $signup = mailjet_subscription_load($signup_id);

    if (!empty($signup) && !is_null($signup)) {
      $form = new \Drupal\mailjet_subscription\Form\SubscriptionSignupPageForm();

      $form_id = 'mailjet_signup_subscribe_block_' . $signup->id() . '_form';
      $form->setFormID($form_id);
      $form->setSignupID($signup->id());

      $content = \Drupal::formBuilder()->getForm($form);
    }

    return $content;
  }

}
