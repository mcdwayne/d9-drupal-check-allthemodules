<?php

namespace Drupal\hover_card\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements the Hover Card admin settings form.
 */
class HoverCardSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'hover_card_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['hover_card.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('hover_card.settings');

    $form['personalization'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Personalization'),
    ];
    $form['personalization']['email_display_status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable User Emails on Hover'),
      '#default_value' => $config->get('email_display_status_value'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('hover_card.settings')->set('email_display_status_value', $form_state->getValue('email_display_status'))->save();
    parent::submitForm($form, $form_state);
  }

}
