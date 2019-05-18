<?php

namespace Drupal\custom_menu_breadcrumbs\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class Config extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'custom_menu_breadcrumbs_form';
  }

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return [
      'custom_menu_breadcrumbs.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    try {
      $form['#tree'] = TRUE;
      // Form constructor.
      $form = parent::buildForm($form, $form_state);
      // Default settings.
      $config = $this->config('custom_menu_breadcrumbs.settings');

      // get all types
      $contentTypes = \Drupal::service('entity.manager')->getStorage('node_type')->loadMultiple();
      asort($contentTypes);

      $form['menu_name'] = array(
        '#type' => 'textfield',
        '#title' => 'Menu machien name',
        '#description' => t('Input menu machine name. eg. main'),
        '#default_value' => $config->get('custom_menu_breadcrumbs.menu_name'),
        '#size' => 20,
      );

      foreach ($contentTypes as $contentType) {
        $id = $contentType->id();
        $label = $contentType->label();

        if ($config->get('custom_menu_breadcrumbs.type_' . $id) == "" ) {
          $default = $config->get('custom_menu_breadcrumbs.menu_name') . ':';
        } else {
          $default = $config->get('custom_menu_breadcrumbs.type_' . $id);
        }
        $form['type_' . $id] = \Drupal::service('menu.parent_form_selector')->parentSelectElement($default, $config->get('custom_menu_breadcrumbs.type_' . $id));
        $form['type_' . $id]['#title'] = $this->t($label);
        $form['type_' . $id]['#description'] = $this->t('Select the last parent menu to display breadcrumb.');
        $form['type_' . $id]['#attributes']['class'][] = 'menu-title-select';
      }

      // User
      $id = "user";
      $label = "User";
      $default = $config->get('custom_menu_breadcrumbs.type_' . $id);
      $form['type_' . $id] = \Drupal::service('menu.parent_form_selector')->parentSelectElement($default, $config->get('custom_menu_breadcrumbs.type_' . $id));
      $form['type_' . $id]['#title'] = $this->t($label);
      $form['type_' . $id]['#description'] = $this->t('Select the last parent menu to display breadcrumb.');
      $form['type_' . $id]['#attributes']['class'][] = 'menu-title-select';

      return $form;
    } catch (\Exception $e) {
      \Drupal::logger('php')->notice(
        'Class: ' . __CLASS__ . ', Function: ' .  __FUNCTION__ . ', Error: %message, Line: %line',
        ['%message' =>  $e->getMessage(), '%line' => $e->getLine()]
      );
    }

  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $config = $this->config('custom_menu_breadcrumbs.settings');

    $config->set(
      'custom_menu_breadcrumbs.menu_name',
      $form_state->getValue('menu_name')
    );

    foreach ($form_state->getCompleteForm() as $key => $value) {

      if (substr($key, 0, 5) == "type_") {

        if ($form_state->getValue('menu_name') . ':' == $form_state->getValue($key)) {
          $config_value = "";
        } else {
          $config_value = $form_state->getValue($key);
        }

        $config->set(
          'custom_menu_breadcrumbs.' . $key, $config_value
        );
      }
    }

    $config->save();

    return parent::submitForm($form, $form_state);
  }
}