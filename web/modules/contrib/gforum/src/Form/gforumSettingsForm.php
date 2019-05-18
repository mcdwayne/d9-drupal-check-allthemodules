<?php

namespace Drupal\gforum\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure gforum settings for this site.
 */
class GforumSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'gforum_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'gforum.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('gforum.settings');

    $form['integration'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Site Integration'),
    ];

    $form['integration']['containers_field_machine_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Containers Field Machine Name'),
      '#description' => t('The machine name of the taxonomy reference field you
                          add to your group type.'),
      '#default_value' => $config->get('containers_field_machine_name'),
      '#required' => TRUE,
    ];

    $form['integration']['group_forum_access_perm'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Group Forum Access Permission'),
      '#description' => t('The permission required to view forum containers
                          and forums.'),
      '#default_value' => $config->get('group_forum_access_perm'),
      '#required' => TRUE,
    ];

    $form['integration']['taxonomy_term_redirect'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Taxonomy Term Redirect'),
      '#description' => t('Redirect from forum taxonomy term pages (e.g.
                          taxonomy/term/123) to forum pages (e.g. forum/123).'),
      '#default_value' => $config->get('taxonomy_term_redirect'),
    ];

    $form['ui'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('User Interface'),
    ];

    $form['ui']['remove_core_create_ui'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Remove core create UI'),
      '#description' => t('Remove the UI that appears above forums when a user
                          has the core "create forum content" permission.  By
                          default the Drupal core adds a button for creating new
                          forum topics, but this may not be desirable in all
                          cases.'),
      '#default_value' => $config->get('remove_core_create_ui'),
    ];

    $form['ui']['remove_core_create_disallowed_message'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Remove core create disallowed message'),
      '#description' => t('Remove the UI that appears above forums when a user
                          does not have the core "create forum content"
                          permission.  By default, the Drupal core adds a "You
                          are not allowed to post new content in the forum"
                          message. Check this box to remove that message.'),
      '#default_value' => $config->get('remove_core_create_disallowed_message'),
    ];

    $form['ui']['topic_create_help'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Topic create help'),
      '#description' => t('A message to display above forums when a user does
                          not have the core "create forum content" permission
                          and "Remove core create disallowed message" is
                          checked.'),
      '#default_value' => $config->get('topic_create_help'),
      '#states' => [
        'visible' => [
          ':input[name="remove_core_create_disallowed_message"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration.
    $this->config('gforum.settings')
      // Set the submitted configuration setting.
      ->set('containers_field_machine_name', $form_state->getValue('containers_field_machine_name'))
      ->set('group_forum_access_perm', $form_state->getValue('group_forum_access_perm'))
      ->set('taxonomy_term_redirect', $form_state->getValue('taxonomy_term_redirect'))
      ->set('remove_core_create_ui', $form_state->getValue('remove_core_create_ui'))
      ->set('remove_core_create_disallowed_message', $form_state->getValue('remove_core_create_disallowed_message'))
      ->set('topic_create_help', $form_state->getValue('topic_create_help'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
