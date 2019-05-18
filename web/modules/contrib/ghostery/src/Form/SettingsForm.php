<?php

namespace Drupal\ghostery\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure ghostery settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ghostery_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['ghostery.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = \Drupal::config('ghostery.settings');
    $options = ['' => '-- None --'] + menu_ui_get_menus();
    $states = [
      'invisible' => [
        "select[name='ghostery_menu']" => ['value' => ''],
      ],
      'required' => [
        "select[name='ghostery_menu']" => ['!value' => ''],
      ],
    ];
    $form['menu'] = [
      '#type' => 'select',
      '#title' => $this->t('Ghostery Menu Item'),
      '#description' => $this->t("Add the Ghostery 'Ad Choices' menu item to this menu."),
      '#options' => $options,
      '#default_value' => $config->get('menu'),
    ];
    $form['pid'] = [
      '#type' => 'textfield',
      '#title' => $this->t('PID'),
      '#default_value' => $config->get('pid'),
      '#states' => $states,
      '#size' => 5,
      '#maxlength' => 10,
    ];
    $form['ocid'] = [
      '#type' => 'textfield',
      '#title' => $this->t('OCID'),
      '#default_value' => $config->get('ocid'),
      '#states' => $states,
      '#size' => 5,
      '#maxlength' => 10,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Make sure a pid and ocid are set if a menu was chosen.
    if (!empty($form_state->getValue('menu'))) {
      if (!is_numeric($form_state->getValue('pid'))) {
        $form_state->setErrorByName('pid', $this->t('The PID must be set and be an integer.'));
      }
      if (!is_numeric($form_state->getValue('ocid'))) {
        $form_state->setErrorByName('ghostery_ocid', $this->t('The OCID must be set and be an integer.'));
      }
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('ghostery.settings')
      ->set('menu', $form_state->getValue('menu'))
      ->set('pid', $form_state->getValue('pid'))
      ->set('ocid', $form_state->getValue('ocid'))
      ->save();
    \Drupal::service('router.builder')->rebuild();
    parent::submitForm($form, $form_state);
  }

}
