<?php

namespace Drupal\google_one_tap\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class GoogleOneTapSettingsForm.
 */
class GoogleOneTapSettingsForm extends ConfigFormBase {

  /**
   * Constructs a new GoogleOneTapSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory object.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'google_one_tap.configuration',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'google_one_tap_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('google_one_tap.configuration');
    $form['settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Settings'),
    ];
    $form['settings']['use_domain_restriction'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use G Suite domain!'),
      '#description' => $this->t('If you want to restrict access to only members of your G Suite domain use this option.'),
      '#default_value' => $config->get('use_domain_restriction'),
    ];
    $form['settings']['domain'] = [
      '#type' => 'textfield',
      '#title' => $this->t('G Suite domain'),
      '#description' => $this->t('Example: domain.com'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('domain'),
      '#states' => [
        'visible' => [
          ':input[name="use_domain_restriction"]' => ['checked' => TRUE],
        ],
        'required' => [
          ':input[name="use_domain_restriction"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $values = $form_state->getValues();
    if (!$values['use_domain_restriction'] && !empty($values['domain'])) {
      $form_state->unsetValue('domain');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('google_one_tap.configuration')
      ->set('use_domain_restriction', $form_state->getValue('use_domain_restriction'))
      ->set('domain', $form_state->getValue('domain'))
      ->save();
  }

}
