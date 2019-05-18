<?php

namespace Drupal\commerce_inventory\Plugin\views\field;

use Drupal\commerce_inventory\QuantityManagerInterface;
use Drupal\Core\Entity\EntityType;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field handler to present the available quantity of an Inventory Item.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("commerce_log_source_label")
 */
class CommerceLogSourceLabel extends FieldPluginBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
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
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    $this->additional_fields['id'] = 'log_id';
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();
    $this->addAdditionalFields();
  }


  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    /** @var \Drupal\commerce_log\Entity\LogInterface $entity */
    $entity = $this->getEntity($values);

    if ($source = $entity->getSourceEntity()) {
      if ($source->hasLinkTemplate('canonical')) {
        return $source->toLink()->toString();
      }
      return $source->label();
    }

    return '';
  }

}
