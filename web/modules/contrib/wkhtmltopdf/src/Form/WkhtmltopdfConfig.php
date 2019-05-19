<?php

namespace Drupal\wkhtmltopdf\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines wkhtmltopdf form configuration.
 */
class WkhtmltopdfConfig extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'wkhtmltopdf.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'wkhtmltopdf_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('wkhtmltopdf.settings');
    $form['wkhtmltopdf_bin'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Path to binary'),
      '#description' => $this->t('Path to wkhtmltopdf binary'),
      '#default_value' => $config->get('wkhtmltopdf_bin'),
    ];

    $form['wkhtmltopdf_zoom'] = [
      '#type' => 'number',
      '#title' => $this->t('Zoom'),
      '#description' => $this->t('Zoom page'),
      '#default_value' => $config->get('wkhtmltopdf_zoom'),
      '#step' => '0.1',
    ];

    $form['wkhtmltopdf_download'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Force download'),
      '#description' => $this->t('Force link download instead redirect'),
      '#default_value' => $config->get('wkhtmltopdf_download'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->configFactory->getEditable('wkhtmltopdf.settings')
      ->set('wkhtmltopdf_bin', $form_state->getValue('wkhtmltopdf_bin'))
      ->set('wkhtmltopdf_zoom', $form_state->getValue('wkhtmltopdf_zoom'))
      ->set('wkhtmltopdf_download', $form_state->getValue('wkhtmltopdf_download'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
