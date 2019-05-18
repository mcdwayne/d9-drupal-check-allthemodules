<?php

namespace Drupal\jquery_touch_swipe\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;


/**
 * Class jQueryTouchSwipeConfigForm.
 *
 * @package Drupal\jquery_touch_swipe\Form
 */
class jQueryTouchSwipeConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'jquery_touch_swipe.jquerytouchswipeconfig',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'j_query_touch_swipe_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('jquery_touch_swipe.jquerytouchswipeconfig');
    $form['enabled_pages'] = [
      '#type' => 'textarea',
      '#title' => $this->t('enabled pages'),
      '#description' => $this->t('The library jQuery Touch Swipe will be enabled on only the following routes, insert 1 route per line'),
      '#default_value' => $config->get('enabled_pages'),
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

    $this->config('jquery_touch_swipe.jquerytouchswipeconfig')
      ->set('enabled_pages', $form_state->getValue('enabled_pages'))
      ->save();
  }

}
