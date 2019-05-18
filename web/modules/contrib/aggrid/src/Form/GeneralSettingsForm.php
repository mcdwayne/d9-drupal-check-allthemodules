<?php

namespace Drupal\aggrid\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class General.
 */
class GeneralSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'aggrid.general',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'general';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('aggrid.general');
    $form['version'] = [
      '#type' => 'radios',
      '#title' => $this->t('Version'),
      '#description' => $this->t('Community is the open edition of ag-Grid. Otherwise for Enterprise (with features like multi-cell selection and Excel export), a license is required. Please see the <a href=":url" target="_blank">ag-Grid library website</a> for license / trial information.', [
        ':url' => 'https://www.ag-grid.com/license-pricing.php',
      ]),
      '#options' => ['Community' => $this->t('Community'), 'Enterprise' => $this->t('Enterprise')],
      '#default_value' => $config->get('version'),
    ];
    $form['source'] = [
      '#type' => 'radios',
      '#title' => $this->t('JS Source'),
      '#description' => $this->t('While the default is ag-Grid library hosted by CDN, local hosting is recommended.'),
      '#options' => ['cdn' => $this->t('CDN'), 'local' => $this->t('Local')],
      '#default_value' => $config->get('source'),
    ];
    $form['license_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Enterprise License Key'),
      '#description' => $this->t('Required for using the Enterprise version.'),
      '#default_value' => $config->get('license_key'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    // Check if library is on local machine. If not, disable local source
    if (!aggrid_library_local_check() && $form_state->getValue('source') == 'local') {
      $form_state->setErrorByName('source', t('The local library is not found. Please see the aggrid warning on the <a href="/admin/reports/status">status report</a>.'));
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('aggrid.general')
      ->set('version', $form_state->getValue('version'))
      ->set('source', $form_state->getValue('source'))
      ->set('license_key', $form_state->getValue('license_key'))
      ->save();
  }

}
