<?php

namespace Drupal\mailchimp_simple_signup\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Provides a 'ClosableButtonBlock' block.
 *
 * @Block(
 *  id = "mailchimp_simple_signup_closable_button",
 *  admin_label = @Translation("Mailchimp closable signup button"),
 * )
 */
class ClosableButtonBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'mailing_list_description' => '',
      'button_label' => $this->t('Subscribe'),
      'signup_form_url' => '',
      'cookie_expire' => 10,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['mailing_list_description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#description' => $this->t('Optional text that explains the destination of the mailing list.'),
      '#default_value' => $this->configuration['mailing_list_description'],
      '#maxlength' => 255,
      '#size' => 64,
      '#weight' => '1',
    ];
    $form['button_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Call to action'),
      '#description' => $this->t('Label that is displayed on the button.'),
      '#default_value' => $this->configuration['button_label'],
      '#maxlength' => 80,
      '#size' => 64,
      '#required' => TRUE,
      '#weight' => '2',
    ];
    $form['signup_form_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Signup form'),
      '#description' => $this->t('The Mailchimp mailing list signup form url.'),
      '#default_value' => $this->configuration['signup_form_url'],
      '#required' => TRUE,
      '#weight' => '3',
    ];
    $form['cookie_expire'] = [
      '#type' => 'number',
      '#title' => $this->t('Cookie expire'),
      '#description' => $this->t('Amount of days before the cookie expires after having closed the signup button.'),
      '#default_value' => $this->configuration['cookie_expire'],
      '#required' => TRUE,
      '#weight' => '4',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['mailing_list_description'] = $form_state->getValue('mailing_list_description');
    $this->configuration['button_label'] = $form_state->getValue('button_label');
    $this->configuration['signup_form_url'] = $form_state->getValue('signup_form_url');
    $this->configuration['cookie_expire'] = $form_state->getValue('cookie_expire');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $buttonUrl = Url::fromUri($this->configuration['signup_form_url']);
    $buttonLink = Link::fromTextAndUrl($this->configuration['button_label'], $buttonUrl);
    $buttonLink = $buttonLink->toRenderable();
    $buttonLink['#attributes'] = ['class' => ['button', 'btn', 'btn-primary']];
    $build = [
      'mailchimp_simple_signup_closable_button' => [
        '#theme' => 'closable_button',
        '#mailing_list_description' => $this->configuration['mailing_list_description'],
        '#button_label' => $this->configuration['button_label'],
        '#signup_form_url' => $this->configuration['signup_form_url'],
        '#button_link' => $buttonLink,
        '#attached' => [
          'library' => [
            'mailchimp_simple_signup/closable_button',
          ],
          'drupalSettings' => [
            'cookie_expire_days' => $this->configuration['cookie_expire'],
          ],
        ],
      ],
    ];
    return $build;
  }

}
