<?php

namespace Drupal\taxonomy_reference_shield\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configuration form for the Entity Reference Shield module.
 *
 * A user can select which entity bundles should this module support.
 */
class ConfigurationForm extends ConfigFormBase {

  /**
   * An array of taxonomy vocabularies.
   *
   * @var array
   *
   * @see \Drupal\Core\Entity\EntityTypeBundleInfoInterface::getAllBundleInfo()
   */
  protected $bundleLabels;

  /**
   * Constructs a SiteInformationForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_bundle_info
   *   A class to retrieve entity type bundles.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeBundleInfoInterface $entity_bundle_info) {
    $this->bundleLabels = $entity_bundle_info->getBundleInfo('taxonomy_term');
    parent::__construct($config_factory);
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
  public function getFormId() {
    return 'taxonomy_reference_shield_configuration_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['taxonomy_reference_shield.config'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $enabled = $this->config('taxonomy_reference_shield.config')->get('enabled');
    $options = [];
    foreach ($this->bundleLabels as $bundle_name => $bundle_data) {
      $options[$bundle_name] = (string) $bundle_data['label'];
    }
    $form['vocabularies'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Protected vocabularies'),
      '#description' => $this->t('Select the vocabularies whose terms should be kept from being deleted if any other entity is referencing them.'),
      '#options' => $options,
      '#default_value' => (array) $enabled,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('taxonomy_reference_shield.config')
      ->set('enabled', $form_state->getValue('vocabularies'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
