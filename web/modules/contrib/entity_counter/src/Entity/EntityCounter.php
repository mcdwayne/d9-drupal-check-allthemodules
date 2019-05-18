<?php

namespace Drupal\entity_counter\Entity;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Plugin\DefaultLazyPluginCollection;
use Drupal\entity_counter\EntityCounterSourceValue;
use Drupal\entity_counter\EntityCounterStatus;
use Drupal\entity_counter\Exception\EntityCounterException;
use Drupal\entity_counter\Plugin\EntityCounterSourceInterface;

/**
 * Defines an entity counter configuration entity class.
 *
 * @ConfigEntityType(
 *   id = "entity_counter",
 *   label = @Translation("Entity counter"),
 *   label_singular = @Translation("Entity counter"),
 *   label_plural = @Translation("Entity counters"),
 *   label_count = @PluralTranslation(
 *     singular = "@count entity counter",
 *     plural = "@count entity counters"
 *   ),
 *   admin_permission = "administer entity_counter",
 *   permission_granularity = "entity_type",
 *   config_prefix = "counter",
 *   handlers = {
 *     "storage" = "Drupal\entity_counter\EntityCounterStorage",
 *     "access" = "Drupal\entity\EntityAccessControlHandler",
 *     "permission_provider" = "Drupal\entity\EntityPermissionProvider",
 *     "list_builder" = "Drupal\entity_counter\EntityCounterListBuilder",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "form" = {
 *       "default" = "Drupal\entity_counter\Form\EntityCounterForm",
 *       "delete" = "Drupal\entity_counter\Form\EntityCounterDeleteForm",
 *       "remove_transactions" = "Drupal\entity_counter\Form\EntityCounterRemoveTransactionsConfirmForm",
 *       "sources" = "Drupal\entity_counter\Form\EntityCounterSourcesForm"
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider"
 *     },
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/entity-counters/add",
 *     "edit-form" = "/admin/structure/entity-counters/{entity_counter}/edit",
 *     "delete-form" = "/admin/structure/entity-counters/{entity_counter}/delete",
 *     "remove-transactions-form" = "/admin/structure/entity-counters/{entity_counter}/remove-transactions",
 *     "enable" = "/admin/structure/entity-counters/{entity_counter}/enable",
 *     "disable" = "/admin/structure/entity-counters/{entity_counter}/disable",
 *     "canonical" = "/admin/structure/entity-counters/{entity_counter}",
 *     "collection" = "/admin/structure/entity-counters"
 *   },
 *   constraints = {
 *     "ValidCounterValueConstraint" = {}
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "status" = "status",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "min",
 *     "max",
 *     "step",
 *     "initial_value",
 *     "sources",
 *     "status"
 *   }
 * )
 */
class EntityCounter extends ConfigEntityBase implements EntityCounterInterface {

  /**
   * The entity counter status.
   *
   * @var string
   */
  protected $status = EntityCounterStatus::OPEN;

  /**
   * The entity counter ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The entity counter label.
   *
   * @var string
   */
  protected $label;

  /**
   * The entity counter description.
   *
   * @var string
   */
  protected $description;

  /**
   * The entity counter min value.
   *
   * @var string
   */
  protected $min;

  /**
   * The entity counter max value.
   *
   * @var string
   */
  protected $max;

  /**
   * The entity counter step value.
   *
   * @var string
   */
  protected $step;

  /**
   * The entity counter initial value.
   *
   * @var string
   */
  protected $initial_value;

  /**
   * The entity counter current value.
   *
   * @var string
   */
  protected $value;

  /**
   * The entity counter sources for this entity counter.
   *
   * @var array
   */
  protected $sources = [];

  /**
   * Track if the entity counter has manual sources.
   *
   * @var bool
   */
  protected $hasManualSources;

  /**
   * Holds the collection of entity counter sources that are used.
   *
   * @var \Drupal\Component\Plugin\LazyPluginCollection
   */
  protected $sourcesCollection;

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($description) {
    $this->description = $description;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getInitialValue() {
    return empty($this->initial_value) ? 0 : $this->initial_value;
  }

  /**
   * {@inheritdoc}
   */
  public function setInitialValue(string $initial_value) {
    $this->initial_value = $initial_value;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMax() {
    return $this->max;
  }

  /**
   * {@inheritdoc}
   */
  public function setMax(string $max) {
    $this->max = $max;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMin() {
    return $this->min;
  }

  /**
   * {@inheritdoc}
   */
  public function setMin(string $min) {
    $this->min = $min;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return [
      'sources' => $this->getSources(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getSource($source_id) {
    return $this->getSources()->get($source_id);
  }

  /**
   * {@inheritdoc}
   */
  public function addSource(EntityCounterSourceInterface $source) {
    $source->setEntityCounter($this);

    $source_id = $source->getSourceId();
    $configuration = $source->getConfiguration();

    $this->getSources()->addInstanceId($source_id, $configuration);
    $this->save();
    $this->resetSources();

    $source->createSource();

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteSource(EntityCounterSourceInterface $source) {
    $source->setEntityCounter($this);

    $this->getSources()->removeInstanceId($source->getSourceId());
    $this->save();
    $this->resetSources();

    $source->deleteSource();

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function updateSource(EntityCounterSourceInterface $source) {
    $source->setEntityCounter($this);

    $source_id = $source->getSourceId();
    $configuration = $source->getConfiguration();
    $original_configuration = $this->getSource($source_id)->getConfiguration();

    $this->getSources()->setInstanceConfiguration($source_id, $configuration);
    $this->save();
    $this->resetSources();

    $source->updateSource($configuration, $original_configuration);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSources($plugin_id = NULL, $status = NULL) {
    if (!$this->sourcesCollection) {
      $this->sourcesCollection = new DefaultLazyPluginCollection($this->getEntityCounterSourcePluginManager(), $this->sources);
      /** @var \Drupal\entity_counter\Plugin\EntityCounterSourceInterface $source */
      foreach ($this->sourcesCollection as $source) {
        // Initialize the source and pass in the entity counter.
        $source->setEntityCounter($this);
      }
      $this->sourcesCollection->sort();
    }

    $sources = $this->sourcesCollection;

    // Clone the sources if they are being filtered.
    if (isset($plugin_id) || isset($status)) {
      $sources = clone $this->sourcesCollection;
    }

    // Filter the sources by plugin id.
    if (isset($plugin_id)) {
      foreach ($sources as $instance_id => $source) {
        if ($source->getPluginId() != $plugin_id) {
          $sources->removeInstanceId($instance_id);
        }
      }
    }

    // Filter the sources by status.
    if (isset($status)) {
      foreach ($sources as $instance_id => $source) {
        if ($source->getStatus() != $status) {
          $sources->removeInstanceId($instance_id);
        }
      }
    }

    return $sources;
  }

  /**
   * {@inheritdoc}
   */
  public function hasManualSources() {
    if (isset($this->hasManualSources)) {
      $this->hasManualSources;
    }

    $this->hasManualSources = FALSE;
    $sources = $this->getSources();
    foreach ($sources as $source) {
      if ($source->getPluginId() == CounterTransactionInterface::MANUAL_TRANSACTION) {
        $this->hasManualSources = TRUE;
        break;
      }
    }

    return $this->hasManualSources;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus() {
    return $this->status;
  }

  /**
   * {@inheritdoc}
   */
  public function setStatus($status) {
    if ($status == EntityCounterStatus::OPEN) {
      $this->status = EntityCounterStatus::OPEN;
    }
    elseif ($status == EntityCounterStatus::CLOSED) {
      $this->status = EntityCounterStatus::CLOSED;
    }
    elseif ($status == EntityCounterStatus::MAX_UPPER_LIMIT) {
      $this->status = EntityCounterStatus::MAX_UPPER_LIMIT;
    }
    else {
      $this->status = ((bool) $status) ? EntityCounterStatus::OPEN : EntityCounterStatus::CLOSED;
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function status() {
    return $this->isOpen();
  }

  /**
   * {@inheritdoc}
   */
  public function hasTransactions(string $source_id = NULL) {
    $query = $this->entityTypeManager()->getStorage('entity_counter_transaction')->getQuery();

    $query->condition('entity_counter.target_id', $this->id());
    if (!empty($source_id)) {
      $query->condition('entity_counter_source.value', $source_id);
    }

    $transactions = $query
      ->range(0, 1)
      ->execute();

    if (count($transactions)) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isClosed() {
    return !$this->isOpen();
  }

  /**
   * {@inheritdoc}
   */
  public function isOpen() {
    switch ($this->status) {
      case EntityCounterStatus::OPEN:
        return TRUE;

      case EntityCounterStatus::CLOSED:
        return FALSE;

      case EntityCounterStatus::MAX_UPPER_LIMIT:
        return $this->getValue(TRUE) < $this->getMax();
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getStep() {
    return $this->step;
  }

  /**
   * {@inheritdoc}
   */
  public function setStep(string $step) {
    $this->step = $step;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(bool $reset = FALSE) {
    if ($reset) {
      $value = \Drupal::state()->get('entity_counter.' . $this->id(), $this->getInitialValue());

      return is_array($value) ? $value['total'] : $value;
    }

    return $this->value;
  }

  /**
   * {@inheritdoc}
   */
  public function updateValue(CounterTransactionInterface $transaction) {
    if ($this->isClosed()) {
      return FALSE;
    }

    $values = \Drupal::state()->get('entity_counter.' . $this->id(), $this->getInitialValue());
    if (!is_array($values)) {
      $values = ['total' => $values];
    }
    if ($transaction->getEntityCounterSource()->valueType() == EntityCounterSourceValue::INCREMENTAL) {
      if (!isset($values['by_source'][$transaction->getEntityCounterSource()->getPluginId()][$transaction->getEntityCounterSourceId()])) {
        $values['by_source'][$transaction->getEntityCounterSource()->getPluginId()][$transaction->getEntityCounterSourceId()] = 0.00;
      }
      $values['by_source'][$transaction->getEntityCounterSource()->getPluginId()][$transaction->getEntityCounterSourceId()] += $transaction->getTransactionValue();
    }
    elseif ($transaction->getEntityCounterSource()->valueType() == EntityCounterSourceValue::ABSOLUTE) {
      $values['by_source'][$transaction->getEntityCounterSource()->getPluginId()][$transaction->getEntityCounterSourceId()] = $transaction->getTransactionValue();
    }
    else {
      throw new EntityCounterException(sprintf('Invalid entity counter transaction type: "%s".', $transaction->getEntityCounterSource()->valueType()));
    }
    $total = 0.00;
    foreach ($values['by_source'] as $source_id) {
      foreach ($source_id as $source) {
        $total += $source;
      }
    }
    $values['total'] = $total;

    \Drupal::state()->set('entity_counter.' . $this->id(), $values);
    $this->invalidateCache();

    return TRUE;
  }

  /**
   * Returns the entity counter source plugin manager.
   *
   * @return \Drupal\entity_counter\Plugin\EntityCounterSourceManager
   *   The entity counter source plugin manager.
   */
  protected function getEntityCounterSourcePluginManager() {
    return \Drupal::service('plugin.manager.entity_counter.source');
  }

  /**
   * Reset cached source settings.
   */
  protected function resetSources() {
    $this->hasManualSources = NULL;
  }

  /**
   * Invalidate cache tags.
   */
  private function invalidateCache() {
    Cache::invalidateTags($this->getCacheTags());
  }

}
