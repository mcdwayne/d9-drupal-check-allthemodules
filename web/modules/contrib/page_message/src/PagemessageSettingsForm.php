<?php
/**
 * @file
 * Contains \Drupal\pagemessage\PagemessageSettingsForm
 */
namespace Drupal\page_message;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure page_message settings for this site.
 */
class PagemessageSettingsForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'page_message_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'page_message.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('page_message.settings');

    $form['page_message_admin_only'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show messages for admin pages only'),
      '#default_value' => $config->get('admin_only', 0),
      '#description' => t('If checked,  Page Message messages will only be shown on admin pages.'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::service('config.factory')->getEditable('page_message.settings');
    $config->set('admin_only', $form_state->getValue('page_message_admin_only'));
    $config->save();

    parent::submitForm($form, $form_state);
  }
}
