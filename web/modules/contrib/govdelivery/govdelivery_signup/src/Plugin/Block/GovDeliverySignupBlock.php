<?php
/**
 * @file
 * Contains \Drupal\govdelivery_signup\Plugin\Block\GovDeliverySignupBlock.
 */
namespace Drupal\govdelivery_signup\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'GovDelivery Signup' block.
 *
 * @Block(
 *   id = "govdelivery_signup_block",
 *   admin_label = @Translation("GovDelivery Signup"),
 *   category = @Translation("Services")
 * )
 */
class GovDeliverySignupBlock extends BlockBase {
  // Override BlockPluginInterface methods here.

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $form = \Drupal::formBuilder()->getForm('Drupal\govdelivery_signup\Form\GovDeliverySignupForm', $config);

    return [
      'govdelivery_signup_form' => $form,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'fieldset_desc' => $this->t('Stay informed'),
      'button_label' => $this->t('Sign me up'),
      'description' => $this->t('Sign up for updates'),
      'email_label' => $this->t('E-mail address'),
      'email_desc' => '',
      'email_placeholder' => 'john@example.com',
      'client_code' => '',
      'server' => 'https://public.govdelivery.com',
      'js_enabled' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    // Retrieve existing configuration for this block.
    $config = $this->getConfiguration();

    $form['govdelivery_signup'] = array(
      '#tree' => TRUE,
    );
    $form['govdelivery_signup']['fieldset_desc'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Signup Box Label'),
      '#default_value' => $config['fieldset_desc'],
      '#maxlength' => 25,
      '#required' => FALSE,
    );
    $form['govdelivery_signup']['button_label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Button Label'),
      '#default_value' => $config['button_label'],
      '#maxlength' => 25,
      '#required' => TRUE,
    );
    $form['govdelivery_signup']['description'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Enter a Description'),
      '#default_value' => $config['description'],
      '#maxlength' => 100,
      '#required' => FALSE,
    );
    $form['govdelivery_signup']['email_label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('E-mail Address Box Label'),
      '#default_value' => $config['email_label'],
      '#maxlength' => 100,
      '#required' => TRUE,
    );
    $form['govdelivery_signup']['email_desc'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('E-mail field description'),
      '#default_value' => $config['email_desc'],
      '#maxlength' => 100,
      '#required' => FALSE,
    );
    $form['govdelivery_signup']['email_placeholder'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('E-mail field placeholder'),
      '#description' => $this->t('Placeholder that is in the e-mail field until the user starts typing.'),
      '#default_value' => $config['email_placeholder'],
      '#maxlength' => 100,
      '#required' => FALSE,
    );
    $form['govdelivery_signup']['client_code'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('GovDelivery DCM Client Account Code'),
      '#default_value' => $config['client_code'],
      '#maxlength' => 20,
      '#required' => TRUE,
    );
    $form['govdelivery_signup']['server'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('GovDelivery DCM URL (Include HTTPS://)'),
      '#default_value' => $config['server'],
      '#maxlength' => 100,
      '#required' => TRUE,
    );

    $form['govdelivery_signup']['js_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable JS'),
      '#description' => $this->t('Allow the submit to be handled with Javascript instead of a POST to the server.'),
      '#default_value' => $config['js_enabled'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    // Save our custom settings when the form is submitted.
    $values = $form_state->getValues()['govdelivery_signup'];
    foreach ($values AS $key => $value) {
      $this->setConfigurationValue($key, $value);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {
  /*
    $fax_number = $form_state->getValue('fax_number');

    if (!is_numeric($fax_number)) {
      $form_state->setErrorByName('fax_block_settings', t('Needs to be an integer'));
    }
  /**/
  }
}
