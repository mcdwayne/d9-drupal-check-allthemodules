<?php

namespace Drupal\sarbacane\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 *
 * @Block(
 *   id = "sarbacane_subscribe",
 *   admin_label = @Translation("Sarbacane subscription form"),
 * )
 *
 */
class SubscribeBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    //TODO dependency injection
    return \Drupal::formBuilder()
      ->getForm('Drupal\sarbacane\Form\SubscribeForm', $this->getConfiguration());
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $text_fields = [
      'accountId' => 'Account ID',
      'apiKey' => 'API key',
      'listId' => 'List ID',
      'description' => 'Description',
      'placeholder' => 'Placeholder',
      'button_text' => 'Submission button text',
      'message' => 'Success message',
    ];
    foreach ($text_fields as $key => $title) {
      $form[$key] = [
        '#type' => 'textfield',
        '#title' => $this->t($title),
        '#default_value' => $this->getConfiguration()[$key],
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $conf = array_merge($this->getConfiguration(), $form_state->getValues());
    $this->setConfiguration($conf);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'listId' => '',
      'accountId' => '',
      'apiKey' => '',
      'description' => '',
      'placeholder' => $this->t('Email address'),
      'button_text' => $this->t('Register'),
      'message' => $this->t('Registration complete! Thank you for your interest.'),
    ];
  }
}
