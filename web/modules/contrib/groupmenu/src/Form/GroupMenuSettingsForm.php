<?php

namespace Drupal\groupmenu\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class GroupMenuSettingsForm.
 */
class GroupMenuSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'groupmenu_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['groupmenu.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $config = $this->config('groupmenu.settings');

    $form['groupmenu_hide_list'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide group menus from menu lists'),
      '#description' => $this->t("Hide group menus from default menu lists. (recommended)"),
      '#default_value' => $config->get('groupmenu_hide_list'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('groupmenu.settings');
    $form_hide_list = $form_state->getValue('groupmenu_hide_list');

    $config->set('groupmenu_hide_list', $form_hide_list)->save();

    parent::submitForm($form, $form_state);
  }

}
