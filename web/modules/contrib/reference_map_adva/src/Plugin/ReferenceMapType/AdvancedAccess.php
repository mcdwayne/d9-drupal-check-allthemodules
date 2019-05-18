<?php

namespace Drupal\reference_map_adva\Plugin\ReferenceMapType;

use Drupal\adva\Plugin\adva\Manager\AccessConsumerManagerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\reference_map\Plugin\ReferenceMapTypeBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the Advance Access reference map plugin type.
 *
 * @ReferenceMapType(
 *   id = "advanced_access",
 *   title = @Translation("Advanced Access"),
 *   help = @Translation("Generates Advanced Access access records and grants
 *     based on reference maps."),
 * )
 */
class AdvancedAccess extends ReferenceMapTypeBase {

  use StringTranslationTrait;

  /**
   * The Access Consumer Plugin Manager.
   *
   * @var \Drupal\adva\Plugin\adva\Manager\AccessConsumerManagerInterface
   */
  protected $consumerManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityFieldManagerInterface $entity_field_manager, EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info, LoggerInterface $logger, Connection $connection, AccessConsumerManagerInterface $consumer_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_field_manager, $entity_type_manager, $entity_type_bundle_info, $logger, $connection);

    $this->consumerManager = $consumer_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_field.manager'),
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('logger.factory')->get('reference_map'),
      $container->get('database'),
      $container->get('plugin.manager.adva.consumer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function configFormAlter(array &$form, FormStateInterface $form_state) {
    $reference_map = $form_state->getFormObject()->getEntity();

    $options = [
      'view' => $this->t('View'),
      'update' => $this->t('Edit'),
      'delete' => $this->t('Delete'),
    ];

    $permissions = !$reference_map->isNew() ? $reference_map->getSetting('permissions') : [
      'view' => 0,
      'update' => 0,
      'delete' => 0,
    ];

    $form['permissions'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Permissions'),
      '#options' => $options,
      '#default_value' => $permissions,
    ];

    $form['submit_text'] = [
      '#markup' => $this->t('On save, all affected access records are deleted and queued to be recreated over time. To create them immediately, choose "Save and Update Access Records".'),
      '#prefix' => '<em>',
      '#suffix' => '</em>',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function configFormActions(array &$form, FormStateInterface $form_state, array &$actions) {
    $actions['batch'] = $actions['submit'];
    $actions['batch']['#value'] = $this->t('Save and Update Access Records');
    $actions['batch']['#submit'][] = [static::class, 'batch'];
  }

  /**
   * {@inheritdoc}
   */
  public function configFormPreSave(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\reference_map\Entity\ReferenceMapConfigInterface $reference_map */
    $reference_map = $form_state->getFormObject()->getEntity();

    // Save the permissions.
    $reference_map->setSetting('permissions', $form_state->getValue('permissions'));

    // If the map or permissions have changed, we need to rebuild access
    // records.
    $rebuild = FALSE;
    if ($this->map) {
      $rebuild = $rebuild || $this->map->map !== $reference_map->map;
      $rebuild = $rebuild || $this->map->getSetting('permissions') !== $reference_map->getSetting('permissions');
    }

    if ($rebuild) {
      // Get a list of all the entities affected by this map.
      $query = \Drupal::entityQuery($reference_map->sourceType);
      // @todo Remove FALSE when the queue() method supports realms.
      if (FALSE && isset($reference_map->sourceBundles)) {
        $query->condition('type', $reference_map->sourceBundles, 'IN');
      }
      $entity_ids = $query->execute();

      // Create new records for these entities.
      if (!empty($entity_ids)) {
        $consumer = $this->consumerManager
          ->getConsumerForEntityTypeId($reference_map->sourceType);

        // @todo Only rebuild access for the realm when supported.
        $consumer->queue($entity_ids);
      }
    }
  }

  /**
   * Batches all queued access records for the entity type.
   *
   * @param array $form
   *   The saved form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of the saved form.
   */
  public static function batch(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\reference_map\Entity\ReferenceMapConfigInterface $reference_map */
    $reference_map = $form_state
      ->getFormObject()
      ->getEntity();

    // Batch process all queued access records for the entity type.
    \Drupal::service('plugin.manager.adva.consumer')
      ->getConsumerForEntityTypeId($reference_map->sourceType)
      ->batch();
  }

  /**
   * Creates advanced access records for the map and the passed in entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to create records for.
   *
   * @return array
   *   The advanced access records for this entity.
   */
  public function getRecords(ContentEntityInterface $entity) {
    $access_records = [];
    $user_ids = $this->follow($entity);
    if (empty($user_ids)) {
      return $access_records;
    }

    $permissions = $this->getSetting('permissions');
    foreach ($user_ids as $user_id) {
      $access_records[] = [
        'realm' => 'reference_map_adva_' . $this->map->id(),
        'gid' => $user_id,
        'grant_view' => $permissions['view'] ? 1 : 0,
        'grant_update' => $permissions['update'] ? 1 : 0,
        'grant_delete' => $permissions['delete'] ? 1 : 0,
      ];
    }

    return $access_records;
  }

}
