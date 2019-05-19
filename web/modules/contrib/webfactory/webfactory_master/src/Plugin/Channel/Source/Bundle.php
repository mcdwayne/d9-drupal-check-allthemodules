<?php

namespace Drupal\webfactory_master\Plugin\Channel\Source;

use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\webfactory_master\Plugin\Channel\ChannelSourceBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Allow to create a channel that expose bundles.
 *
 * @ChannelSource(
 *   id = "bundle",
 *   label = @Translation("Bundle")
 * )
 */
class Bundle extends ChannelSourceBase implements ContainerFactoryPluginInterface {

  /**
   * The entity query service.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfo
   */
  protected $entityTypeBundleInfo;

  /**
   * Constructs a Drupal\rest\Plugin\ResourceBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param QueryFactory $entity_query
   *   The entity query service.
   * @param EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerInterface $logger, QueryFactory $entity_query, EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $logger);
    $this->entityQuery = $entity_query;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory')->get('webfactory_master'),
      $container->get('entity.query'),
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * Query used to get the entities.
   *
   * @var mixed|\Drupal\Core\Entity\Query\QueryInterface
   */
  protected $query = NULL;

  /**
   * {@inheritdoc}
   */
  public function entities($limit = NULL, $offset = NULL) {
    $entities = array();
    $types = array();
    $entity_type = $this->channelEntity->get('entity_type');

    if (isset($this->settings['bundle'])) {
      $bundles = $this->settings['bundle'];
      foreach ($bundles as $bundle) {
        $types[] = $bundle;
      }
    }

    $bundle_prop = $this->getBundleTypeProperty($entity_type);

    $this->query = $this->entityQuery->get($entity_type, 'AND');
    if (!empty($types)) {
      $this->query->condition($bundle_prop, $types, 'IN');
    }

    if (isset($offset) && isset($limit)) {
      // Limit, offset.
      $this->query->range($offset, $limit);
    }

    $entities_id = $this->query->execute();

    if (!empty($entities_id)) {
      $entities = $this->entityTypeManager->getStorage($entity_type)->loadMultiple($entities_id);
    }

    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function getNbTotalEntities() {
    // Get Total number of elements.
    $count_query = clone $this->query;

    // 999999999 because Drupal 8 doesn't allow to unset a range by calling
    // it without parameter like in Drupal 7.
    return $count_query->count()->range(0, 999999999)->execute();
  }

  /**
   * Return plugin settings form.
   *
   * @param array $form
   *   The form element.
   * @param FormStateInterface $form_state
   *   The form state element.
   *
   * @return mixed
   *   The form.
   */
  public function getSettingsForm(array $form, FormStateInterface $form_state) {
    $bundle_values = [];

    if (isset($this->settings['bundle'])) {
      foreach ($this->settings['bundle'] as $bundle) {
        $bundle_values[] = $bundle;
      }
    }

    $entity_type = $this->channelEntity->get('entity_type');

    $bundle_form['bundle'] = [
      '#type' => 'select',
      '#title' => $this->t('Bundles'),
      '#multiple' => TRUE,
      '#default_value' => $bundle_values,
      '#prefix' => '<div id="edit-bundle-list-wrapper">',
      '#suffix' => '</div>',
    ];

    $bundle_form['bundle']['#options'] = $this->getBundleList($entity_type);

    return $bundle_form;
  }

  /**
   * Retrieve settings to store in channel entity.
   *
   * @param array $form
   *   The form element.
   * @param FormStateInterface $form_state
   *   The form state element.
   *
   * @return array
   *   get settings list.
   */
  public function getSettings(array $form, FormStateInterface $form_state) {
    $settings = ['bundle' => []];

    $entities = $form_state->getValue('bundle');
    foreach ($entities as $bundle_id => $selected) {
      if ($selected != '0') {
        $settings['bundle'][] = $bundle_id;
      }
    }

    return $settings;
  }

  /**
   * Helper to retrieve bundle from given entities.
   *
   * @param string $entity_type
   *   Entity type.
   *
   * @return array
   *   Bundle options.
   */
  protected function getBundleList($entity_type) {
    $bundles_options = [];
    $bundles = $this->entityTypeBundleInfo->getBundleInfo($entity_type);
    foreach ($bundles as $bundle_id => $bundle) {
      $bundles_options[$bundle_id] = $bundle['label'];
    }

    return $bundles_options;
  }

  /**
   * Get bundle type property.
   *
   * @param string $entity_type
   *   The entity type.
   *
   * @return null
   *   The bundle type property.
   */
  protected function getBundleTypeProperty($entity_type) {
    $bundle_prop = NULL;
    $definitions = $this->entityTypeManager->getDefinitions();
    if (isset($definitions[$entity_type])) {
      $entity_keys = $definitions[$entity_type]->get('entity_keys');
      if (isset($entity_keys['bundle'])) {
        $bundle_prop = $entity_keys['bundle'];
      }
    }
    return $bundle_prop;
  }

}
