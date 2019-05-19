<?php

namespace Drupal\views_timelinejs\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure site-wide settings for Views TimelineJS.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'views_timelinejs_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['views_timelinejs.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('views_timelinejs.settings');

    $form['library_location'] = [
      '#type' => 'radios',
      '#title' => $this->t('TimelineJS library location'),
      '#description' => $this->t('If serving the files from a local path, the library MUST be located in libraries/TimelineJS3.  See the module README file for more information.'),
      '#options' => [
        'cdn' => $this->t('NU Knight Lab CDN'),
        'local' => $this->t('Local path (libraries/TimelineJS3)'),
      ],
      '#default_value' => $config->get('library_location'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('views_timelinejs.settings')
      ->set('library_location', $values['library_location'])
      ->save();
    parent::submitForm($form, $form_state);
  }

}
