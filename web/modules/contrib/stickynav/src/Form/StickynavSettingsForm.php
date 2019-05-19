<?php

namespace Drupal\stickynav\Form;

use \Drupal\Core\Form\ConfigFormBase;
use \Drupal\Core\Form\FormStateInterface;
use \Drupal\Core\User;
use \Drupal\Core\Url;

/**
 * Build Sticky Navigation settings form.
 */
class StickynavSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'stickynav_config_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['stickynav.settings.' . $this->getRequest()->attributes->get('theme')];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $theme = '') {
    $config = $this->config('stickynav.settings.' . $theme);
    $form['enabled'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable'),
      '#default_value' => $config->get('enabled') ? $config->get('enabled') : FALSE,
    );

    $states = array(
      'visible' => array(
        ':input[name="enabled"]' => array('checked' => TRUE),
      ),
      'invisible' => array(
        ':input[name="enabled"]' => array('checked' => FALSE),
      ),
    );

    // Selector is only visible when you activate sticky nav for the theme.
    $form['selector'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Selector'),
      '#description' => $this->t('Place your selector for your menu that will be sticky on your theme. Use jquery format.'),
      '#default_value' => $config->get('selector') ? $config->get('selector') : '',
      '#states' => $states,
    );

    $form['offset'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Offset Selector'),
      '#description' => $this->t('Element to use as an offset. For multiple elements on the page separate them with a comma. Use jquery format.'),
      '#default_value' => $config->get('offset') ? $config->get('offset') : '',
      '#states' => $states,
    );

    $form['custom_offset'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Custom offset'),
      '#description' => $this->t('Custom offset in pixels. This will be added to the elements offsets if they are set.'),
      '#default_value' => $config->get('custom_offset') ? $config->get('custom_offset') : '',
      '#states' => $states,
    );

    $role_options = array();
    $roles = user_roles();
    foreach ($roles as $role) {
      $role_options[$role->id()] = $role->label();
    }

    $form['roles'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('Excluded Roles'),
      '#description' => $this->t("Exclude specific roles from using sticky navigation."),
      '#options' => $role_options,
      '#default_value' => $config->get('roles') ? $config->get('roles') : array(),
      '#states' => $states,
    );

    $form['theme'] = [
      '#type' => 'value',
      '#value' => $theme,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $theme = $form_state->getValue('theme');
    $config = $this->config('stickynav.settings.' . $theme);
    $config
      ->set('enabled', $form_state->getValue('enabled'))
      ->set('selector', $form_state->getValue('selector'))
      ->set('offset', $form_state->getValue('offset'))
      ->set('custom_offset', $form_state->getValue('custom_offset'))
      ->set('roles', array_keys(array_filter($form_state->getValue('roles'))))
      ->save();
    $form_state->setRedirect('stickynav.set_admin');
    parent::submitForm($form, $form_state);
  }

}
