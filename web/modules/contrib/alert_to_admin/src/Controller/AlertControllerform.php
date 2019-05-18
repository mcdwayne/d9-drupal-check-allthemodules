<?php

/**
 * @file
 * Contains \Drupal\alert_to_admin\AlertController.
 */

namespace Drupal\alert_to_admin\Controller;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;

/**
 * Controller for Alert to Administrator.
 */
class AlertControllerform extends ConfigFormBase {

  /**
   * Implement alert_to_admin function.
   */
    public function getFormId() {
    return 'alert_to_admin_settings';
  }

 /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'alert_to_admin.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('alert_to_admin.settings');
    $form['alert_to_admin_message_text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Text of the alert message'),
	  '#description' => t('Enter the text that site administrators will see at the top of most forms on the site.'),
      '#default_value' => $config->get('alert_to_admin_message_text'),
    ];

    $form['alert_to_admin_excluded_form_ids'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Form IDs to exclude the alert message from (advanced)'),
	  '#description' => t('Enter a list of form IDs for which the site administrator alert will <em>not</em> be shown. Put each form ID on a separate line. The site administrator alert will be shown at the top of all forms except those listed here.'),
      '#default_value' => $config->get('alert_to_admin_excluded_form_ids'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('alert_to_admin.settings')
      ->set('alert_to_admin_message_text', $form_state->getValue('alert_to_admin_message_text'))
	  ->set('alert_to_admin_excluded_form_ids', $form_state->getValue('alert_to_admin_excluded_form_ids'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
