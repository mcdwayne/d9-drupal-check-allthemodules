<?php
/**
 * @file
 * Contains \Drupal\accessibility_content\Form\AccessibilityContentAdminForm.
 */

namespace Drupal\accessibility_content\Form;

use Drupal\system\SystemConfigFormBase;

/**
 * Defines a form to configure maintenance settings for this site.
 */
class AccessibilityContentAdminForm extends SystemConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $config = $this->configFactory->get('accessibility.accessibility_content');

    $form['auto_check'] = array(
      '#type' => 'checkbox',
      '#title' => t('Automatically check page'),
      '#default_value' => $config->get('auto_check'),
    );
    
    $form['show_toggle'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show accessibility check toggle'),
      '#default_value' => $config->get('show_toggle'),
    );
    
    $form['toggle'] = array(
      '#type' => 'fieldset',
      '#title' => t('Toggle settings'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );
    
    $form['toggle']['toggle_message_off'] = array(
      '#type' => 'textfield',
      '#title' => t('Message to show when accessibility checking is off'),
      '#default_value' => ($config->get('toggle_message_off')) ? $config->get('toggle_message_off') : t('Check page for accessibility'),
    );
    
    $form['toggle']['toggle_message_on'] = array(
      '#type' => 'textfield',
      '#title' => t('Message to show when accessibility checking is on'),
      '#default_value' => ($config->get('toggle_message_on')) ? $config->get('toggle_message_on') : t('Hide checks for accessibility'),
    );

    $form['disabled_form_message'] = array(
      '#type' => 'textarea',
      '#title' => t('Message to show when a user is prevented from submitting a form because of accessibility errors'),
      '#default_value' => ($config->get('disabled_form_message')) ? $config->get('disabled_form_message') :
         t('You have been prevented from submitting this form because of the following accessibility errors.'),
      );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $this->configFactory->get('accessibility.accessibility_content')
      ->set('auto_check', $form_state['values']['auto_check'])
      ->set('show_toggle', $form_state['values']['show_toggle'])
      ->set('toggle_message_off', $form_state['values']['toggle_message_off'])
      ->set('toggle_message_on', $form_state['values']['toggle_message_on'])
      ->set('disabled_form_message', $form_state['values']['disabled_form_message'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}