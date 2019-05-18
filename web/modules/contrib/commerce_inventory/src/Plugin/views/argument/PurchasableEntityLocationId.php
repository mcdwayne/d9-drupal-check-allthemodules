<?php

namespace Drupal\commerce_inventory\Plugin\views\argument;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\views\Plugin\views\argument\ArgumentPluginBase;

/**
 * Argument handler to match current location id for purchasable entities.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("purchasable_entity_location_id")
 */
class PurchasableEntityLocationId extends ArgumentPluginBase {

  /**
   * The operator used for the query: or|and.
   *
   * @var string
   */
  public $operator;

  /**
   * The actual value which is used for querying.
   *
   * @var array
   */
  public $value;

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['limit'] = ['default' => FALSE];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['limit'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Limit to non-tracked items'),
      '#description' => $this->t("If selected, this location will limit to purchasable entities not currently in the location's inventory."),
      '#default_value' => !empty($this->options['limit']),
      '#group' => 'options][more',
    ];

  }

  /**
   * {@inheritdoc}
   */
  public function title() {
    if ($this->getValue() && $location = \Drupal::entityTypeManager()->getStorage('commerce_inventory_location')->load($this->getValue())) {
      return $location->label();
    }
    return !empty($this->definition['empty field name']) ? $this->definition['empty field name'] : $this->t('(Missing location)');
  }

  /**
   * Override for specific title lookups.
   *
   * @return array
   *   Returns all titles, if it's just one title it's an array with one entry.
   */
  public function titleQuery() {
    return $this->value;
  }

  /**
   * {@inheritdoc}
   */
  public function query($group_by = FALSE) {
    $this->ensureMyTable();

    // Limit the items by the location if set.
    if (!empty($this->options['limit'])) {
      $display = $this->view->storage->addCacheTags(['commerce_inventory_item_list']);

      /** @var \Drupal\views\Plugin\views\query\Sql $query */
      $query = &$this->query;

      // Use this entity type and table alias instead of base in case this
      // argument is using a relationship.
      $pe_type = \Drupal::entityTypeManager()->getDefinition($this->getEntityType());
      $pe_id = $pe_type->getKey('id');

      // Build out select query to be used for WHERE NOT EXISTS.
      $connection = \Drupal::database();
      $inventory_items_query = $connection->select('commerce_inventory_item');
      $inventory_items_query->where("$this->tableAlias.$pe_id = commerce_inventory_item.purchasable_entity__target_id");
      $inventory_items_query->condition('purchasable_entity__target_type', $pe_type->id());
      $inventory_items_query->condition('location_id', $this->getValue());
      $inventory_items_query->addField('commerce_inventory_item', 'purchasable_entity__target_id');

      // Add select query as a WHERE NOT EXISTS.
      $query->addWhere(0, NULL, $inventory_items_query, 'NOT EXISTS');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSortName() {
    return $this->t('Numerical', [], ['context' => 'Sort order']);
  }

  /**
   * {@inheritdoc}
   */
  public function getContextDefinition() {
    if ($context_definition = parent::getContextDefinition()) {
      return $context_definition;
    }

    // If the parent does not provide a context definition through the
    // validation plugin, fall back to the integer type.
    return new ContextDefinition('integer', $this->adminLabel(), FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $tags = parent::getCacheTags();

    // Clear cache when a items are added/removed/updated if limited.
    if (!empty($this->options['limit'])) {
      $tags = Cache::mergeTags($tags, ['commerce_inventory_item_list']);
    }

    return $tags;
  }

}
