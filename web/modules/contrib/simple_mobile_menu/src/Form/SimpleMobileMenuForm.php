<?php

namespace Drupal\simple_mobile_menu\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a simple mobile menu settings form.
 */
class SimpleMobileMenuForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'simple_mobile_menu';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'simple_mobile_menu.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $simple_mobile_menu_config = $this->config('simple_mobile_menu.settings');
    // Get list of current menu Types.
    $simple_menu = \Drupal::service('entity.manager')->getStorage('menu')->loadMultiple();
    $options = [];
    foreach ($simple_menu as $menus) {
      $options[$menus->id()] = $menus->label();
    }
    $form['menu_name'] = [
      '#type' => 'select',
      '#options' => $options,
      '#description' => t('<br/><em><b>Note:</b> To override the default css of simple mobile menu, please add below classes in your theme styles.css file and assign font, color etc. properties. <br/><b> main_menu, sub_menu </b></em>'),
      '#default_value' => $simple_mobile_menu_config->get('menu_name'),
      '#title' => t('List of Available Menus'),
    ];
    return parent::buildForm($form, $form_state);
    // Leave an empty line here.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('simple_mobile_menu.settings')
      ->set('menu_name', $form_state->getValue('menu_name'))
      ->save();
    parent::submitForm($form, $form_state);
    // Theme this form as a config form.
    $form['#theme'] = 'system_config_form';
  }

  // Leave an empty line here.
}
