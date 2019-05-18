<?php

namespace Drupal\lockr\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;

class LockrAdvancedForm implements ContainerInjectionInterface, FormInterface {

  /** @var ConfigFactoryInterface */
  protected $configFactory;

  /** @var FileSystemInterface */
  protected $fileSystem;

  public function __construct(
    ConfigFactoryInterface $config_factory,
    FileSystemInterface $file_system
  ) {
    $this->configFactory = $config_factory;
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('file_system')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'lockr_advanced_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['fs'] = [
      '#type' => 'details',
      '#title' => 'Advanced',
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];

    $config = $this->configFactory->get('lockr.settings');

    $form['fs']['region'] = [
      '#type' => 'select',
      '#title' => 'Region',
      '#default_value' => $config->get('region'),
      '#empty_option' => '- Nearest -',
      '#options' => [
        'us' => 'US',
        'eu' => 'EU',
      ],
    ];

    $form['fs']['custom'] = [
      '#type' => 'checkbox',
      '#title' => 'Set custom certificate location',
      '#default_value' => $config->get('custom', FALSE),
    ];

    $form['fs']['custom_cert'] = [
      '#type' => 'textfield',
      '#title' => 'Certificate path',
      '#default_value' => $config->get('cert_path'),
      '#states' => [
        'visible' => [':input[name="custom"]' => ['checked' => TRUE]],
      ],
    ];

    $form['fs']['submit'] = [
      '#type' => 'submit',
      '#value' => 'Save',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!$form_state->getValue('custom')) {
      return;
    }

    $cert_path = $form_state->getValue('custom_cert');

    if (!$cert_path) {
      $form_state->setErrorByName(
        'custom_cert',
        $this->t('Certificate location is required for custom certs')
      );
      return;
    }

    $real_cert_path = $this->fileSystem->realpath($cert_path);
    if (is_dir($real_cert_path) || !is_readable($real_cert_path)) {
      $form_state->setErrorByName(
        'custom_cert',
        'Certificate must be a readable file'
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $custom = $form_state->getValue('custom');
    $region = $form_state->getValue('region');

    $config = $this->configFactory->getEditable('lockr.settings');

    if ($region) {
      $config->set('region', $region);
    }
    else {
      $config->clear('region');
    }

    $config->set('custom', $custom);
    if ($custom) {
      $config->set('cert_path', $form_state->getValue('custom_cert'));
    }
    else {
      $config->clear('custom_cert');
    }
    $config->save();
  }

}
