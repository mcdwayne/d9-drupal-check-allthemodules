<?php

namespace Drupal\drd\Plugin\Auth;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Plugin implementation of the 'shared_secret' DRD authentication.
 *
 * @Auth(
 *   id = "shared_secret",
 *   label = @Translation("Shared Secret")
 * )
 */
class SharedSecret extends Base {
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array &$form, array $condition) {
    $form['secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Shared secret'),
      '#default_value' => '',
      '#states' => [
        'required' => $condition,
      ],
    ];
    $form['description'] = [
      '#markup' => $this->t('You can authenticate with a shared secret, a string that only this DRD instance the the remote domains will be aware of.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsFormValues(FormStateInterface $form_state) {
    /* @var \Drupal\drd\Encryption $service */
    $service = \Drupal::service('drd.encrypt');
    $settings = ['secret' => $form_state->getValue('secret')];
    $service->encrypt($settings);
    return $settings;
  }

}
