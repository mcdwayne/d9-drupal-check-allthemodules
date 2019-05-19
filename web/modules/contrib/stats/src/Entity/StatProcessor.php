<?php

namespace Drupal\stats\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Defines the Stat processor entity.
 *
 * @ConfigEntityType(
 *   id = "stat_processor",
 *   label = @Translation("Stat processor"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\stats\StatProcessorListBuilder",
 *     "form" = {
 *       "add" = "Drupal\stats\Form\StatProcessorForm",
 *       "edit" = "Drupal\stats\Form\StatProcessorForm",
 *       "delete" = "Drupal\stats\Form\StatProcessorDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\stats\StatProcessorHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "stat_processor",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/stat_processor/{stat_processor}",
 *     "add-form" = "/admin/structure/stat_processor/add",
 *     "edit-form" = "/admin/structure/stat_processor/{stat_processor}/edit",
 *     "delete-form" = "/admin/structure/stat_processor/{stat_processor}/delete",
 *     "collection" = "/admin/structure/stat_processor"
 *   }
 * )
 */
class StatProcessor extends ConfigEntityBase implements StatProcessorInterface {

  /**
   * The Stat processor ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Stat processor label.
   *
   * @var string
   */
  protected $label;

  /**
   * @var string
   */
  protected $triggerEntityType = '';

  /**
   * @var string
   */
  protected $triggerBundle = '';

  /**
   * @var array
   */
  protected $dependencies = [];

  /**
   * @var array
   */
  protected $tags = [];

  /**
   * @var array
   */
  protected $source;

  /**
   * @var array
   */
  protected $destination;

  /**
   * @var array
   */
  protected $steps = [];

  /**
   * @var int
   */
  protected $weight = 0;

  /**
   * {@inheritdoc}
   */
  public function getTriggerEntityType(): string {
    return $this->triggerEntityType;
  }

  /**
   * {@inheritdoc}
   */
  public function setTriggerEntityType(string $triggerEntityType): StatProcessorInterface {
    $this->triggerEntityType = $triggerEntityType;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTriggerBundle(): string {
    return $this->triggerBundle;
  }

  /**
   * {@inheritdoc}
   */
  public function setTriggerBundle(string $triggerBundle): StatProcessorInterface {
    $this->triggerBundle = $triggerBundle;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies(): array {
    return $this->dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function setDependencies(array $dependencies): StatProcessorInterface {
    $this->dependencies = $dependencies;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTags(): array {
    return $this->tags;
  }

  /**
   * {@inheritdoc}
   */
  public function setTags(array $tags): StatProcessorInterface {
    $this->tags = $tags;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function supportsTriggerEntity(ContentEntityInterface $entity) {
    if ($entity->getEntityTypeId() !== $this->getTriggerEntityType()) {
      return FALSE;
    }

    if (!empty($this->getTriggerBundle()) && $entity->bundle() !== $this->getTriggerBundle()) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSourcePluginID() {
    return $this->source['plugin'];
  }

  /**
   * {@inheritdoc}
   */
  public function getSource(): array {
    return $this->source;
  }

  /**
   * {@inheritdoc}
   */
  public function setSource(array $source): StatProcessorInterface {
    $this->source = $source;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDestinationPluginID() {
    return $this->destination['plugin'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDestination(): array {
    return $this->destination;
  }

  /**
   * {@inheritdoc}
   */
  public function setDestination(array $destination): StatProcessorInterface {
    $this->destination = $destination;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSteps(): array {
    return $this->steps;
  }

  /**
   * {@inheritdoc}
   */
  public function setSteps(array $steps): StatProcessorInterface {
    $this->steps = $steps;
    return $this;
  }

  /**
   * @return int
   */
  public function getWeight(): int {
    return $this->weight;
  }

  /**
   * @param int $weight
   *
   * @return StatProcessor
   */
  public function setWeight(int $weight): StatProcessorInterface {
    $this->weight = $weight;
    return $this;
  }



}
