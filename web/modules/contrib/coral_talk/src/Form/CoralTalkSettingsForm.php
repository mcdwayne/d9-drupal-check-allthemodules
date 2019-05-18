<?php

namespace Drupal\coral_talk\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfo;

/**
 * Class CoralTalkSettingsForm.
 */
class CoralTalkSettingsForm extends ConfigFormBase {

  /**
   * Drupal\Core\Entity\EntityTypeBundleInfo definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfo
   */
  protected $entityTypeBundleInfo;

  /**
   * Constructor for this form.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    EntityTypeBundleInfo $entity_type_bundle_info
  ) {
    parent::__construct($config_factory);
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'coral_talk.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'coral_talk_settings_form';
  }

  /**
   * Gets an array of node type bundles.
   */
  protected function getBundleOptions($entity_type = 'node') {
    $bundles = $this->entityTypeBundleInfo->getBundleInfo($entity_type);
    $options = [];

    foreach ($bundles as $machine_name => $bundle) {
      $options[$machine_name] = $bundle['label'];
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('coral_talk.settings');

    $form['domain'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Host Domain'),
      '#description' => $this->t('Provide the address to your Coral Talk instance.'),
      '#default_value' => $config->get('domain') ?? '',
      '#required' => TRUE,
    ];

    $form['content_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Content Types'),
      '#description' => $this->t('Select which content types to enable comments for.'),
      '#options' => $this->getBundleOptions(),
      '#default_value' => $config->get('content_types') ?? [],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('coral_talk.settings')
      ->set('domain', $form_state->getValue('domain'))
      ->set('content_types', $form_state->getValue('content_types'))
      ->save();
  }

}
