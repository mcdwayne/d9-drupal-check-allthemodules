<?php

namespace Drupal\stock_photo_search\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * StockPhotosSettingsForm class.
 */
class StockPhotoSettingsForm extends ConfigFormBase {

  /**
   * Implements getFormId().
   */
  public function getFormId() {
    return 'stock_photo_search_settings';
  }

  /**
   * Implements getEditableConfigNames().
   */
  protected function getEditableConfigNames() {
    return ['stock_photo_search.settings'];
  }

  /**
   * Implements buildForm().
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['settings'] = [
      '#type' => 'vertical_tabs',
    ];

    $form['info'] = [
      '#type' => 'details',
      '#title' => $this->t('Info'),
      '#open' => TRUE,
      '#group' => 'settings',
      '#description' => $this->t('When you activate one of the stock photo providers you can configure your API keys here.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
  }

}
