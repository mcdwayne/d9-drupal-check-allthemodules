<?php

namespace Drupal\shortcutperrole\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\shortcut\Entity\ShortcutSet;

/**
 * Configure Shortcut per Role settings for this site.
 */
class ShortcutPerRoleSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'shortcutperrole_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['shortcutperrole.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('shortcutperrole.settings');
    $roles = user_roles();
    $sets = ShortcutSet::loadMultiple();

    $options = array();
    foreach ($sets as $name => $set) {
      $options[$name] = $set->label();
    }

    $form['#title'] = $this->t('Assign Shortcuts for Role');

    foreach ($roles as $rid => $role) {
      $default_value_ss= $config->get('role.' . $rid);
      $form['shortcutperrole'] [$rid ] = array(
        '#type' => 'select',
        '#default_value' => isset($default_value_ss) ? $default_value_ss : 'default',
        '#options' =>  $options,
        '#title' => $role->label(),
      );
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();
    foreach ($form_state->getValues() as $role => $set) {
      $this->config('shortcutperrole.settings')
        ->set('role.' . $role, $set)
        ->save();
    }

    parent::submitForm($form, $form_state);
  }

}
