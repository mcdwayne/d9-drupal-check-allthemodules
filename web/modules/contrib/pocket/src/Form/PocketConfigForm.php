<?php

namespace Drupal\pocket\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class PocketConfigForm.
 */
class PocketConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['pocket.config'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'pocket_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('pocket.config');
    $form['key'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Platform key'),
      '#description'   => $this->t(
        'Enter a platform key for the Pocket service (generated <a href=":url">here</a>).',
        [
          ':url' => 'https://getpocket.com/developer/apps/new',
        ]
      ),
      '#default_value' => $config->get('key'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('pocket.config')
      ->set('key', $form_state->getValue('key'))
      ->save();
  }

}
