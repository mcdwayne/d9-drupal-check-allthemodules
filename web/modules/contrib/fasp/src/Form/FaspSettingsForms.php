<?php

namespace Drupal\fasp\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * {@inheritdoc}
 */
class FaspSettingsForms extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'fasp_settings_forms';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'fasp.settings.forms',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('fasp.settings.forms');

    $form['forms'] = [
      '#type' => 'textarea',
      '#title' => $this->t("Form ID's"),
      '#description' => $this->t("Specify form id's by using their machine names. The '*' character is a wildcard. Example: <em>node_*_form</em>."),
      '#default_value' => $config->get('forms'),
    ];

    $form['match_type'] = [
      '#type' => 'radios',
      '#default_value' => $config->get('match_type'),
      '#options' => [
        0 => $this->t('Protect only listed forms'),
        1 => $this->t('Disable protection for listed forms (all others will be protected)'),
      ],
    ];

    $form['exclude_views_exposed_forms'] = [
      '#type' => 'checkbox',
      '#default_value' => $config->get('exclude_views_exposed_forms'),
      '#title' => $this->t('Exclude exposed views forms'),
      '#states' => [
        'visible' => [
          'input[name="match_type"]' => [
            'value' => 1,
          ],
        ],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration and set new values.
    \Drupal::configFactory()->getEditable('fasp.settings.forms')
      ->set('forms', $form_state->getValue('forms'))
      ->set('match_type', $form_state->getValue('match_type'))
      ->set('exclude_views_exposed_forms', $form_state->getValue('exclude_views_exposed_forms'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
