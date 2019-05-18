<?php

namespace Drupal\commerce_claim_gift_aid\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class CommerceGiftAidConfigClass.
 */
class CommerceClaimGiftAidConfigClass extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'commerce_claim_gift_aid.commerce_gift_aid_text',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_claim_gift_aid_config';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory()->get('commerce_claim_gift_aid.commerce_gift_aid_text');

    drupal_set_message($this->t('You can view further on what you should include in your gift aid text <a target="_blank" href="@link">here</a>', [
      '@link' => 'https://www.gov.uk/claim-gift-aid',
    ]));

    $form['gift_aid_text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Gift aid text'),
      '#required' => TRUE,
      '#description' => $this->t('Enter some text which will appear on the order checkout pane'),
      '#default_value' => $config->get('gift_aid_text'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('commerce_claim_gift_aid.commerce_gift_aid_text')
      ->set('gift_aid_text', $form_state->getValue('gift_aid_text'))
      ->save();
  }

}
