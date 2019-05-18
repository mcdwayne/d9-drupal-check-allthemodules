<?php

/**
 * @file
 */

namespace Drupal\mailjet_sign_up\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;


/**
 * @Block(
 * id = "mailjet_sign_up",
 * admin_label = @Translation("Mailjet sign up"),
 * )
 */
class SignUpBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    //TODO DI
    return \Drupal::formBuilder()
      ->getForm('Drupal\mailjet_sign_up\Form\SignUpForm', $this->getConfiguration());
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'header' => $this->t('Subscribe our newsletter'),
      'placeholder' => $this->t('Your email address'),
      'api_key' => '',
      'secret_key' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['header'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Header'),
      '#description' => $this->t('This text will appear upon the email field.'),
      '#default_value' => $this->configuration['header'],
    ];
    $form['placeholder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Placeholder'),
      '#description' => $this->t('This text will appear in the email field when empty.'),
      '#default_value' => $this->configuration['placeholder'],
    ];
    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Mailjet API key'),
      '#description' => $this->t('See https://app.mailjet.com/account/api_keys.'),
      '#default_value' => $this->configuration['api_key'],
    ];
    $form['secret_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Mailjet secret key'),
      '#description' => $this->t('See https://app.mailjet.com/account/api_keys.'),
      '#default_value' => $this->configuration['secret_key'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    foreach ([
               'header',
               'placeholder',
               'api_key',
               'secret_key',
             ] as $config_name) {
      $this->configuration[$config_name] = trim($form_state->getValue($config_name));
    }
  }


}
