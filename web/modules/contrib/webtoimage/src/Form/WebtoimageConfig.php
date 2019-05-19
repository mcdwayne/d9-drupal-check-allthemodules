<?php

namespace Drupal\webtoimage\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines webtoimage form configuration.
 */
class WebtoimageConfig extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'webtoimage.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webtoimage_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('webtoimage.settings');

    $form['webtoimage_bin'] = [
      '#type' => 'textfield',
      '#title' => $this->t('image path to binary'),
      '#description' => $this->t('Path to webtoimage binary'),
      '#default_value' => $config->get('webtoimage_bin'),
    ];

    $form['webtoimage_extension'] = array(
      '#type' => 'select',
      '#title' => t('Extension'),
      '#options' => array(
          'jpg' => t('jpg'),
          'png' => t('png'),
          'bmp' => t('bmp'),
          'svg' => t('svg'),
      ),
      '#default_value' => $config->get('webtoimage_extension'),
    );

    $form['webtoimage_zoom'] = [
      '#type' => 'number',
      '#title' => $this->t('Zoom'),
      '#description' => $this->t('Zoom page'),
      '#default_value' => $config->get('webtoimage_zoom'),
      '#step' => '0.1',
    ];

    $form['webtoimage_download'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Force download'),
      '#description' => $this->t('Force link download instead redirect'),
      '#default_value' => $config->get('webtoimage_download'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->configFactory->getEditable('webtoimage.settings')
      ->set('webtoimage_bin', $form_state->getValue('webtoimage_bin'))
      ->set('webtoimage_zoom', $form_state->getValue('webtoimage_zoom'))
      ->set('webtoimage_download', $form_state->getValue('webtoimage_download'))
      ->set('webtoimage_extension', $form_state->getValue('webtoimage_extension'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
