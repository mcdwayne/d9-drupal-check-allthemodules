<?php

namespace Drupal\aws\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\aws\Aws;

/**
 * Configure site information settings for this site.
 */
class ServiceConfigForm extends ConfigFormBase {

  /**
   * An array of configuration names that should be editable.
   *
   * @var array
   */
  protected $editableConfig = [];

  /**
   * The config key for the specified service.
   *
   * @var string
   */
  protected $configKey = '';

  /**
   * Constructs a SiteInformationForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\aws\Aws $aws
   *   The Aws object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, Aws $aws) {
    parent::__construct($config_factory);
    $this->aws = $aws;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('aws')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'aws_service_config_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return $this->editableConfig;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $service_id = '') {
    $form = parent::buildForm($form, $form_state);

    $this->configKey = sprintf('aws.%s.settings', $service_id);
    $this->editableConfig = [$this->configKey];
    /** @var \Drupal\Core\Config\Config $config */
    $config = $this->config($this->configKey);

    $profile_options = [];
    /** @var \Drupal\aws\Entity\Profile $profile */
    foreach ($this->aws->getProfiles() as $profile) {
      $profile_options += [
        $profile->id() => $profile->label(),
      ];
    }

    $form['profile'] = [
      '#title' => $this->t('Profile'),
      '#description' => $this->t('The profile that will be used to authenticate this service.'),
      '#type' => 'select',
      '#options' => $profile_options,
      '#default_value' => $config->get('profile'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config($this->configKey);

    $config->set('profile', $form_state->getValue('profile'));
    $config->save();
  }

}
