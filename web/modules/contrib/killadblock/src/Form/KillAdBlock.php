<?php

namespace Drupal\killadblock\Form;

/**
*@file
* Contains Drupal\killadblock\Form\KillAdBlock.
*/

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\Role;

/**
 * Class KillAdBlock.
 */
class KillAdBlock extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'killadblock.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'killadblock_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('killadblock.settings');

    $form['kadb_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#description' => $this->t('Enter title for visitiors'),
      '#default_value' => $config->get('kadb_title'),
    ];
    $form['kadb_description'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Description'),
      '#description' => $this->t('Enter Description  or reason why user whitelist your website  you can use html add logo or hardcode html'),
      '#default_value' => $config->get('kadb_description.value'),
    ];
    $form['kadb_btn_txt'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Button Text'),
      '#description' => $this->t('Add message to the button'),
      '#default_value' => $config->get('kadb_btn_txt'),
    ];
    $defaultRoles = $config->get('roles');
    $roles = Role::loadMultiple();
    $options = [];
    foreach ($roles as $role) {
      $options[$role->id()] = $role->label();
    }

    $form['roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Select roles to show Adblock popup'),
      '#options' => $options,
      '#default_value' => isset($defaultRoles) ? $defaultRoles : FALSE,
    ];

    $form['cache'] = [
      '#cache' => [
        'max-age' => 0,
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $config = $this->config('killadblock.settings');

    $title = $form_state->getValue('kadb_title');
    $description = $form_state->getValue('kadb_description')['value'];
    $btntxt = $form_state->getValue('kadb_btn_txt');
    $roles = $form_state->getValue('roles');
    $config->set('kadb_title', $title)
      ->set('kadb_description.value', $description)
      ->set('kadb_btn_txt', $btntxt)
      ->set('roles', $roles)
      ->save();
  }

}
