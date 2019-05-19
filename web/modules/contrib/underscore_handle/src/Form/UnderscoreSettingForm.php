<?php

namespace Drupal\underscore_handle\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class underscoreSettingForm.
 */
class UnderscoreSettingForm extends ConfigFormBase {

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new underscoreSettingForm object.
   */
  public function __construct(
  ConfigFactoryInterface $config_factory
  ) {
    parent::__construct($config_factory);
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('config.factory'), $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'underscore_handle.underscoresetting',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'underscore_setting_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config                    = $this->config('underscore_handle.underscoresetting');
    $form['end_underscore']    = [
      '#type' => 'checkbox',
      '#title' => $this->t('End Underscore'),
      '#description' => $this->t('Validate field end underscore'),
      '#default_value' => $config->get('end_underscore'),
    ];
    $form['double_underscore'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Double underscore'),
      '#description' => $this->t('Validate Dobule Underscore'),
      '#default_value' => $config->get('double_underscore'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('underscore_handle.underscoresetting')
      ->set('end_underscore', $form_state->getValue('end_underscore'))
      ->set('double_underscore', $form_state->getValue('double_underscore'))
      ->save();
  }

}
