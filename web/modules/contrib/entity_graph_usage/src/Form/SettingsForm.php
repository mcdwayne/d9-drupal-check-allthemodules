<?php

namespace Drupal\entity_graph_usage\Form;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SettingsForm.
 *
 * @package Drupal\entity_graph_usage\Form
 */
class SettingsForm extends ConfigFormBase {

  /**
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Routing\RouteBuilder
   */
  protected $routerBuilder;

  /**
   * SettingsForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entityTypeBundleInfo
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   * @param \Drupal\Core\Routing\RouteBuilder $routeBuilder
   */
  public function __construct(ConfigFactoryInterface $configFactory, EntityTypeBundleInfoInterface $entityTypeBundleInfo, EntityTypeManagerInterface $entityTypeManager, $routeBuilder) {
    parent::__construct($configFactory);
    $this->entityTypeBundleInfo = $entityTypeBundleInfo;
    $this->entityTypeManager = $entityTypeManager;
    $this->routerBuilder = $routeBuilder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity_type.manager'),
      $container->get('router.builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'entity_graph_usage.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_graph_usage_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $typesConfig = $this->config('entity_graph_usage.settings')->get('entity_types') ?: [];

    /**
     * @var string $entityType
     * @var EntityTypeInterface $definition
     */
    foreach ($this->getEntityTypes() as $entityType => $entityTypeDefinition) {
      $options = [];
      foreach ($this->entityTypeBundleInfo->getBundleInfo($entityType) as $bundleId => $bundle) {
        $options[$bundleId] = $bundle['label'];
      }

      $form[$entityType] = [
        '#type' => 'checkboxes',
        '#title' => $entityTypeDefinition->getLabel(),
        '#description' => $this->t('Show usage tab for these bundles.'),
        '#options' => $options,
        '#default_value' => isset($typesConfig[$entityType]) ? $typesConfig[$entityType] : [],
      ];
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $typesConfig = [];
    foreach ($this->getEntityTypes() as $entityType => $definition) {
      $bundles = array_filter($form_state->getValue($entityType, []));
      if (!empty($bundles)) {
        $typesConfig[$entityType] = $bundles;
      }
    }

    $this->config('entity_graph_usage.settings')
      ->set('entity_types', $typesConfig)
      ->save();

    $this->routerBuilder->rebuild();
  }

  /**
   * Returns the entity types that have a canonical link template.
   *
   * @return EntityTypeInterface[]
   */
  protected function getEntityTypes() {
    return array_filter($this->entityTypeManager->getDefinitions(), function (EntityTypeInterface $definition) {
      return $definition->hasLinkTemplate('canonical');
    });
  }

}
