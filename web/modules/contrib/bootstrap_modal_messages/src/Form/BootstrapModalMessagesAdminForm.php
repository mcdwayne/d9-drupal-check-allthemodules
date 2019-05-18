<?php

namespace Drupal\bootstrap_modal_messages\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class BootstrapModalMessagesAdminForm.
 *
 * @package Drupal\bootstrap_modal_messages\Form
 */
class BootstrapModalMessagesAdminForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bootstrap_modal_messages_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['bootstrap_modal_messages.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('bootstrap_modal_messages.settings');

    $form['bootstrap_modal_messages_selector'] = array(
      '#type' => 'textfield',
      '#title' => t('Messages div selector'),
      '#default_value' => \Drupal::config('bootstrap_modal_messages.settings')->get('bootstrap_modal_messages_selector'),
      '#description' => t('The CSS/JS selector to find messages for the modal.'),
    );

    $form['bootstrap_modal_messages_multiple'] = array(
      '#type' => 'select',
      '#title' => t('Multiple messages setting'),
      '#options' => array(
        'single' => t('Single modal'),
        'multiple' => t('Multiple - New modal per message type'),
      ),
      '#default_value' => \Drupal::config('bootstrap_modal_messages.settings')->get('bootstrap_modal_messages_multiple'),
      '#description' => t('How to handle multiple messages on one page?'),
    );

    $form['bootstrap_modal_messages_ignore_admin'] = array(
      '#type' => 'select',
      '#title' => t('Ignore admin pages'),
      '#options' => array(
        1 => t('Yes'),
        0 => t('No'),
      ),
      '#default_value' => \Drupal::config('bootstrap_modal_messages.settings')->get('bootstrap_modal_messages_ignore_admin'),
      '#description' => t('Ignore admin pages so that messages display normally?'),
    );

    // Modal header.
    $form['modal_header'] = array(
      '#type' => 'fieldset',
      '#title' => t('Modal Header'),
      '#collapsible' => TRUE,
    );

    $form['modal_header']['bootstrap_modal_messages_show_header'] = array(
      '#type' => 'select',
      '#title' => t('Show header'),
      '#options' => array(
        1 => t('Yes'),
        0 => t('No'),
      ),
      '#default_value' => \Drupal::config('bootstrap_modal_messages.settings')->get('bootstrap_modal_messages_show_header'),
      '#description' => t('Show the modal header.'),
    );

    $title = $config->get('bootstrap_modal_messages_title');
    $form['modal_header']['bootstrap_modal_messages_title'] = array(
      '#type' => 'text_format',
      '#title' => t('Modal Title'),
      '#default_value' => $title['value'] ?: '',
      '#format' => $title['format'] ?: 'plain_text',
      '#description' => t('Enter text to be used in the modal title. Defaults to "Messages".'),
      '#states' => array(
        'visible' => array(
          ':input[name="bootstrap_modal_messages_show_header"]' => array('value' => 1),
        ),
      ),
    );

    $form['modal_header']['bootstrap_modal_messages_header_close'] = array(
      '#type' => 'select',
      '#title' => t('Show close button (X)'),
      '#options' => array(
        1 => t('Yes'),
        0 => t('No'),
      ),
      '#default_value' => \Drupal::config('bootstrap_modal_messages.settings')->get('bootstrap_modal_messages_header_close'),
      '#description' => t('Show the close (X) button in modal header.'),
    );

    // Modal footer.
    $form['modal_footer'] = array(
      '#type' => 'fieldset',
      '#title' => t('Modal Footer'),
      '#collapsible' => TRUE,
    );

    $form['modal_footer']['bootstrap_modal_messages_show_footer'] = array(
      '#type' => 'select',
      '#title' => t('Show footer'),
      '#options' => array(
        1 => t('Yes'),
        0 => t('No'),
      ),
      '#default_value' => \Drupal::config('bootstrap_modal_messages.settings')->get('bootstrap_modal_messages_show_footer'),
      '#description' => t('Show the modal footer.'),
    );

    $footer = $config->get('bootstrap_modal_messages_footer_html');
    $form['modal_footer']['bootstrap_modal_messages_footer_html'] = array(
      '#type' => 'text_format',
      '#title' => t('Footer HTML'),
      '#default_value' => $footer['value'] ?: '',
      '#format' => $footer['format'] ?: 'plain_text',
      '#description' => t("Enter text to be displayed in the footer. Leave empty to use Bootstrap's default Close button."),
      '#states' => array(
        'visible' => array(
          ':input[name="bootstrap_modal_messages_show_footer"]' => array('value' => 1),
        ),
      ),
    );

    // Modal controls.
    $form['controls'] = array(
      '#type' => 'fieldset',
      '#title' => t('Controls'),
      '#collapsible' => TRUE,
    );

    $form['controls']['bootstrap_modal_messages_show_onload'] = array(
      '#type' => 'select',
      '#title' => t('Open modal on page load?'),
      '#options' => array(
        1 => t('Yes'),
        0 => t('No - Should enable "Show controls" to display modal'),
      ),
      '#default_value' => \Drupal::config('bootstrap_modal_messages.settings')->get('bootstrap_modal_messages_show_onload'),
      '#description' => t('Open modal immediately when the page loads.'),
    );

    $form['controls']['bootstrap_modal_messages_onload_expiration'] = array(
      '#type' => 'number',
      '#title' => t('Time (in seconds) browser waits to display messages again.'),
      '#min' => 0,
      '#max' => 86400,
      '#default_value' => \Drupal::config('bootstrap_modal_messages.settings')->get('bootstrap_modal_messages_onload_expiration'),
      '#description' => t('Upon subsequent page loads, the "show onload" will not happen until this time has elapsed. Useful for single, sitewide messages.'),
      '#states' => array(
        'visible' => array(
          ':input[name="bootstrap_modal_messages_show_onload"]' => array('value' => 1),
        ),
      ),
    );

    $form['controls']['bootstrap_modal_messages_show_controls'] = array(
      '#type' => 'select',
      '#title' => t('Show controls?'),
      '#options' => array(
        0 => t('No'),
        1 => t('Yes'),
      ),
      '#default_value' => \Drupal::config('bootstrap_modal_messages.settings')->get('bootstrap_modal_messages_show_controls'),
      '#description' => t('Creates a div that allows you to show messages again. <strong>This setting can be overridden by the permission "View Bootstrap Modal Messages controls".</strong>'),
    );

    $controls = $config->get('bootstrap_modal_messages_controls_html');
    $form['controls']['bootstrap_modal_messages_controls_html'] = array(
      '#type' => 'text_format',
      '#title' => t('Controls HTML'),
      '#default_value' => $controls['value'] ?: '',
      '#format' => $controls['format'] ?: 'plain_text',
      '#description' => t('Enter the HTML to be used in the div for controls.'),
      '#states' => array(
        'visible' => array(
          ':input[name="bootstrap_modal_messages_show_controls"]' => array('value' => 1),
        ),
      ),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $values = $form_state->getValues();
    $this->config('bootstrap_modal_messages.settings')
      ->set('bootstrap_modal_messages_selector', $values['bootstrap_modal_messages_selector'])
      ->set('bootstrap_modal_messages_multiple', $values['bootstrap_modal_messages_multiple'])
      ->set('bootstrap_modal_messages_ignore_admin', $values['bootstrap_modal_messages_ignore_admin'])
      ->set('bootstrap_modal_messages_show_header', $values['bootstrap_modal_messages_show_header'])
      ->set('bootstrap_modal_messages_title', $values['bootstrap_modal_messages_title'])
      ->set('bootstrap_modal_messages_header_close', $values['bootstrap_modal_messages_header_close'])
      ->set('bootstrap_modal_messages_show_footer', $values['bootstrap_modal_messages_show_footer'])
      ->set('bootstrap_modal_messages_footer_html', $values['bootstrap_modal_messages_footer_html'])
      ->set('bootstrap_modal_messages_show_onload', $values['bootstrap_modal_messages_show_onload'])
      ->set('bootstrap_modal_messages_show_controls', $values['bootstrap_modal_messages_show_controls'])
      ->set('bootstrap_modal_messages_controls_html', $values['bootstrap_modal_messages_controls_html'])
      ->set('bootstrap_modal_messages_onload_expiration', $values['bootstrap_modal_messages_onload_expiration'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
