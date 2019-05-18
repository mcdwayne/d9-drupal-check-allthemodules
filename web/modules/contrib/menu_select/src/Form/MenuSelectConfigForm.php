<?php

namespace Drupal\menu_select\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Controller for Menu Select config form.
 */
class MenuSelectConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'menu_select_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'menu_select.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $default_config = $this->configFactory('menu_select.settings');
    return array(
      'search_enabled' => $default_config->get('menu_select.settings.search_enabled'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->get('menu_select.settings');
    $form['menu_select_search_enabled'] = array(
      '#title' => $this->t('Enable searching for a menu link'),
      '#description' => $this->t('Allows users to search for a menu link by name.'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('menu_select.search_enabled'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('menu_select.settings');

    $config->set('menu_select.search_enabled', $form_state->getValue('menu_select_search_enabled'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
