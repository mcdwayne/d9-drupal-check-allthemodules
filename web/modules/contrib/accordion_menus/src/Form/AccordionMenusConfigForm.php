<?php

namespace Drupal\accordion_menus\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure accordion menus settings.
 */
class AccordionMenusConfigForm extends ConfigFormBase {

  /**
   * Config settings.
   */
  const SETTINGS = 'accordion_menus.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'accordion_menus_config_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);

    // Get list of menus.
    $menus = menu_ui_get_menus();
    $form['accordion_menus'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Accondion Menus'),
      '#options' => $menus,
      '#description' => $this->t('Select each menu to make them accordion menu independently.'),
      '#default_value' => !empty($config->get('accordion_menus')) ? $config->get('accordion_menus') : [],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration and Set the submitted configuration setting.
    $this->configFactory->getEditable(static::SETTINGS)
      ->set('accordion_menus', $form_state->getValue('accordion_menus'))
      ->save();

    parent::submitForm($form, $form_state);

    // Clear cache is needed to effect this value on block derivetive plugin
    // system. See @src/Plugin/Derivative/AccordionMenusBlock.
    drupal_flush_all_caches();
  }

}
