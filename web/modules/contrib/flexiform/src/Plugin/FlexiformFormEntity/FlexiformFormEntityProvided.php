<?php

namespace Drupal\flexiform\Plugin\FlexiformFormEntity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\flexiform\FormEntity\FlexiformFormEntityBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form Entity plugin.
 *
 * For entities that are passed in through the configuration
 * like the base entity.
 *
 * @FlexiformFormEntity(
 *   id = "provided",
 *   label = @Translation("Provided Entity"),
 * )
 */
class FlexiformFormEntityProvided extends FlexiformFormEntityBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityBundleInfo;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    EntityTypeBundleInfoInterface $entity_bundle_info
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->entityBundleInfo = $entity_bundle_info;
  }

  /**
   * Create a new instance of this plugin.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container to pull out services.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @return FlexiformFormEntityProvided
   *   The new instance of this plugin.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getEntity() {
    if (isset($this->configuration['entity'])
        && ($this->configuration['entity'] instanceof EntityInterface)) {
      return $this->configuration['entity'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityType() {
    return $this->configuration['entity_type'];
  }

  /**
   * {@inheritdoc}
   */
  public function getBundle() {
    return $this->configuration['bundle'];
  }

  /**
   * {@inheritdoc}
   */
  public function configurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::configurationForm($form, $form_state);

    $entity_type_options = $bundle_options = [];
    foreach ($this->entityTypeManager->getDefinitions() as $id => $entity_type) {
      if ($entity_type->isSubclassOf('\Drupal\Core\Entity\FieldableEntityInterface')) {
        $entity_type_options[$id] = $entity_type->getLabel();
      }
    }
    $form['entity_type'] = [
      '#type' => 'select',
      '#options' => $entity_type_options,
      '#title' => $this->t('Entity Type'),
      '#description' => $this->t('The entity type.'),
      '#default_value' => !empty($this->configuration['entity_type']) ? $this->configuration['entity_type'] : '',
      '#ajax' => [
        'wrapper' => 'bundle-select-wrapper',
        'callback' => [$this, 'ajaxBundleElementCallback'],
      ],
    ];

    $entity_type_id = $form['entity_type']['#default_value'];
    if ($form_state->getValue('bundle')) {
      $entity_type_id = $form_state->getValue('bundle');
    }
    if ($input = $form_state->getUserInput()) {
      $entity_type_id = $input['configuration']['entity_type'] ?: NULL;
    }

    if (!empty($entity_type_id)) {
      foreach ($this->entityBundleInfo->getBundleInfo($entity_type_id) as $bundle => $info) {
        $bundle_options[$bundle] = $info['label'];
      }
    }
    $form['bundle'] = [
      '#prefix' => '<div id="bundle-select-wrapper">',
      '#suffix' => '</div>',
      '#type' => 'select',
      '#options' => $bundle_options,
      '#title' => $this->t('Bundle'),
      '#description' => $this->t('The bundle.'),
      '#default_value' => !empty($this->configuration['bundle']) ? $this->configuration['bundle'] : '',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function ajaxBundleElementCallback(array $form, FormStateInterface $form_state) {
    return $form['configuration']['bundle'];
  }

}
