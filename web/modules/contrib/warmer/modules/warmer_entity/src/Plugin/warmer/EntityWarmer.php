<?php

namespace Drupal\warmer_entity\Plugin\warmer;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\SubformStateInterface;
use Drupal\warmer\Plugin\WarmerPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The cache warmer for the built-in entity cache.
 *
 * @Warmer(
 *   id = "entity",
 *   label = @Translation("Entity"),
 *   description = @Translation("Loads entities from the selected entity types & bundles to warm the entity cache.")
 * )
 */
final class EntityWarmer extends WarmerPluginBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * The list of all item IDs for all entities in the system.
   *
   * Consists of <entity-type-id>:<entity-id>.
   *
   * @var array
   */
  private $iids = [];

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    assert($instance instanceof EntityWarmer);
    $instance->setEntityTypeManager($container->get('entity_type.manager'));
    return $instance;
  }

  /**
   * Injects the entity type manager.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function setEntityTypeManager(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultiple(array $ids = []) {
    $ids_per_type = array_reduce($ids, function ($carry, $id) {
      list($entity_type_id, $entity_id) = explode(':', $id);
      if (empty($carry[$entity_type_id])) {
        $carry[$entity_type_id] = [];
      }
      $carry[$entity_type_id][] = $entity_id;
      return $carry;
    }, []);
    $output = [];
    foreach ($ids_per_type as $entity_type_id => $entity_ids) {
      try {
        $output += $this->entityTypeManager
          ->getStorage($entity_type_id)
          ->loadMultiple($entity_ids);
      }
      catch (PluginException $exception) {
        watchdog_exception('warmer', $exception);
      }
      catch (DatabaseExceptionWrapper $exception) {
        watchdog_exception('warmer', $exception);
      }
    }
    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function warmMultiple(array $items = []) {
    // The entity load already warms the entity cache. Do nothing.
    return count($items);
  }

  /**
   * {@inheritdoc}
   * TODO: This is a naive implementation.
   */
  public function buildIdsBatch($cursor) {
    $configuration = $this->getConfiguration();
    if (empty($this->iids) && !empty($configuration['entity_types'])) {
      $entity_bundle_pairs = array_filter(array_values($configuration['entity_types']));
      sort($entity_bundle_pairs);
      $this->iids = array_reduce($entity_bundle_pairs, function ($iids, $entity_bundle_pair) {
        list($entity_type_id, $bundle) = explode(':', $entity_bundle_pair);
        $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
        $bundle_key = $entity_type->getKey('bundle');
        $id_key = $entity_type->getKey('id');
        $query = $this->entityTypeManager
          ->getStorage($entity_type_id)
          ->getQuery();
        if (!empty($id_key)) {
          $query->sort($id_key);
        }
        if (!empty($bundle_key)) {
          $query->condition($bundle_key, $bundle);
        }
        $results = $query->execute();
        $entity_ids = array_filter((array) array_values($results));
        $iids = array_merge($iids, array_map(
          function ($id) use ($entity_type_id) {
            return sprintf('%s:%s', $entity_type_id, $id);
          },
          $entity_ids
        ));
        return $iids;
      }, []);
    }
    $cursor_position = is_null($cursor) ? -1 : array_search($cursor, $this->iids);
    if ($cursor_position === FALSE) {
      return [];
    }
    return array_slice($this->iids, $cursor_position + 1, (int) $this->getBatchSize());
  }

  /**
   * {@inheritdoc}
   */
  public function addMoreConfigurationFormElements(array $form, SubformStateInterface $form_state) {
    /** @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bunle_info */
    $bunle_info = \Drupal::service('entity_type.bundle.info');
    $options = [];
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type) {
      $bundles = $bunle_info->getBundleInfo($entity_type->id());
      $label = (string) $entity_type->getLabel();
      $entity_type_id = $entity_type->id();
      $options[$label] = [];
      foreach ($bundles as $bundle_id => $bundle_data) {
        $options[$label][sprintf('%s:%s', $entity_type_id, $bundle_id)] = $bundle_data['label'];
      }
    }
    $configuration = $this->getConfiguration();
    $form['entity_types'] = [
      '#type' => 'select',
      '#title' => $this->t('Entity Types'),
      '#description' => $this->t('Enable the entity types to warm asynchronously.'),
      '#options' => $options,
      '#default_value' => empty($configuration['entity_types']) ? [] : $configuration['entity_types'],
      '#multiple' => TRUE,
      '#attributes' => ['style' => 'min-height: 60em;']
    ];

    return $form;
  }

}
