<?php

namespace Drupal\cookie_content_blocker\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form builder to manage settings related to the Cookie content blocker.
 *
 * @package Drupal\cookie_content_blocker\Form
 */
class BlockerSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'cookie_content_blocker_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['cookie_content_blocker.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('cookie_content_blocker.settings');

    $form['blocked_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Default message for blocked content'),
      '#description' => $this->t('When content is blocked and a message is shown, this message will be shown by default. Leave empty to use the default. Some basic HTML can be used.'),
      '#default_value' => $config->get('blocked_message') ?? $this->t('You have not yet given permission to place the required cookies. Accept the required cookies to view this content.'),
    ];

    $form['show_button'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show a button to change cookie consent below the message for blocked content'),
      '#description' => $this->t('When the button is shown the click event to change cookie consent will be turned on.'),
      '#default_value' => $config->get('show_button') ?? TRUE,
    ];

    $form['button_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('The change cookie consent button text'),
      '#default_value' => $config->get('button_text') ?? $this->t('Show content'),
      '#states' => [
        'visible' => [
          ':input[name="show_button"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['enable_click_consent_change'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable changing consent by clicking on the blocked content'),
      '#description' => $this->t('To show the blocked content, consent to the placement of cookies has to be given. By enabling this setting clicking on the blocked content wrapper will let the user change consent.'),
      '#default_value' => $config->get('enable_click_consent_change') ?? TRUE,
      '#states' => [
        'visible' => [
          ':input[name="show_button"]' => ['checked' => FALSE],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    parent::submitForm($form, $form_state);

    $config = $this->config('cookie_content_blocker.settings');
    $config->set('blocked_message', $form_state->getValue('blocked_message'));
    $config->set('show_button', $form_state->getValue('show_button'));
    $config->set('button_text', $form_state->getValue('button_text'));
    $config->set('enable_click_consent_change', $form_state->getValue('enable_click_consent_change'));
    $config->save();
  }

}
