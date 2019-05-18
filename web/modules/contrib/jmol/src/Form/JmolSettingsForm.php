<?php
namespace Drupal\jmol\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Jmol settings for this site.
 */
class JmolSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'jmol_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'jmol.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('jmol.settings');

    $form['jmol_version'] = array(
      '#type' => 'select',
      '#options' => ['full' => $this->t('Full Featured'), 'lite' => $this->t('Lite')],
      '#title' => $this->t('Which library would you like to use?'),
      '#default_value' => $config->get('version'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::service('config.factory')->getEditable('jmol.settings');
    $config->set('version', $form_state->getValue('jmol_version'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
