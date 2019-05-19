<?php

namespace Drupal\simple_access\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Profiles simple_access_settings_page form.
 */
class Settings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simple_access_settings_page';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['simple_access.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('simple_access.settings');

    $options = [
      'view' => $this->t('<strong>View</strong>: Displays viewability selections at top of node form. Selected access groups will be the only users who can view the node. All unselected = normal node behavior (viewable by all).<br />'),
      'update' => $this->t('<strong>Edit</strong>: Displays editability selections at top of node form. Users who are part of selected access groups will be able to edit this node. All unselected = "normal" node behavior (only author and admins may edit).<br />'),
      'delete' => $this->t('<strong>Delete</strong>: Displays deleteability selections at top of node form. Users who are part of selected access groups will be able to delete this node. All unselected = "normal" node behavior (only author and admins may delete).<br />'),
    ];
    $form['display'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Display'),
      '#default_value' => $config->get('display'),
      '#options' => $options,
      '#description' => $this->t('Which options should appear on node add/edit pages for administrators? Select at least one.'),
      '#required' => TRUE,
    ];
    $form['show_groups'] = [
      '#type' => 'checkbox',
      '#title' => 'Show groups even when user is not a member.',
      '#default_value' => $config->get('show_groups'),
      '#description' => $this->t('This is useful when you want to have a user be able to make content viewable by themselves and a higher privileged group (e.g. students sharing work with faculty)'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory()->getEditable('simple_access.settings')
      ->set('display', $form_state->getValue('display'))
      ->set('show_groups', $form_state->getValue('show_groups'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
