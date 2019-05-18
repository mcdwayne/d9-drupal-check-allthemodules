<?php

namespace Drupal\migrate_qa\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Handler to display summary of flags on a tracker.
 *
 * @ViewsField("migrate_qa_tracker_flags_summary")
 */
class TrackerFlagsSummary extends FieldPluginBase {

  /**
   * The EntityTypeManager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  private $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    // Loop through flags that reference this tracker and build a list that
    // summarizes each one.
    $id = $values->_entity->id();
    $query = $this->entityTypeManager
      ->getStorage('migrate_qa_flag')
      ->getQuery();

    $flag_ids = $query
      ->condition('tracker', $id, '=')
      ->sort('field', 'ASC')
      // @todo Sort by term name instead of tid.
      ->sort('flag_type', 'ASC')
      ->execute();

    $flags = $this->entityTypeManager
      ->getStorage('migrate_qa_flag')
      ->loadMultiple($flag_ids);

    $items = [];
    foreach ($flags as $flag) {
      $field = $flag->field[0]->value;
      $flag_type = $flag->get('flag_type');
      $flag_type_name = '';
      if (!$flag_type->isEmpty()) {
        $flag_type_name = $flag_type->referencedEntities()[0]->label();
      }
      $details_count = $flag->details->count();
      $items[] = t('@field: @type (@count)', [
        '@field' => $field,
        '@type' => $flag_type_name,
        '@count' => $details_count,
      ]);
    }

    $render_array = [
      '#theme' => 'item_list',
      '#list_type' => 'ul',
      '#items' => $items,
    ];

    return $render_array;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Do nothing, because the field is computed only in the render method.
  }

}
