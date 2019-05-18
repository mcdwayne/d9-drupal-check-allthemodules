<?php

namespace Drupal\oh_regular\Entity;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\field\FieldConfigInterface;
use Drupal\oh_regular\OhRegularMapInterface;

/**
 * Oh Map entity.
 *
 * @ConfigEntityType(
 *   id = "oh_regular_map",
 *   label = @Translation("Opening hours mapping"),
 *   label_collection = @Translation("Opening hours mappings"),
 *   label_singular = @Translation("opening hours mapping"),
 *   label_plural = @Translation("opening hours mappings"),
 *   label_count = @PluralTranslation(
 *     singular = "@count opening hours mapping",
 *     plural = "@count opening hours mappings"
 *   ),
 *   admin_permission = "administer oh_regular_map",
 *   config_prefix = "map",
 *   entity_keys = {
 *     "id" = "id"
 *   },
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\oh_regular\Entity\Form\OhRegularMapForm",
 *       "default" = "Drupal\oh_regular\Entity\Form\OhRegularMapForm",
 *       "edit" = "Drupal\oh_regular\Entity\Form\OhRegularMapForm",
 *       "delete" = "Drupal\oh_regular\Entity\Form\OhRegularMapDeleteForm"
 *     },
 *     "list_builder" = "Drupal\oh_regular\OhRegularMapListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\oh_regular\Entity\Routing\OhRegularMapRouteProvider",
 *     }
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/oh-regular/add",
 *     "edit-form" = "/admin/structure/oh-regular/manage/{oh_regular_map}",
 *     "delete-form" = "/admin/structure/oh-regular/manage/{oh_regular_map}/delete",
 *     "reset-form" = "/admin/structure/oh-regular/manage/{oh_regular_map}/reset",
 *     "overview-form" = "/admin/structure/oh-regular/manage/{oh_regular_map}/overview",
 *     "collection" = "/admin/structure/oh-regular",
 *   },
 * )
 */
class OhRegularMap extends ConfigEntityBase implements OhRegularMapInterface {

  use StringTranslationTrait;

  const CACHE_TAG_ALL = 'config_oh_regular_map_all';

  /**
   * Computed ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The entity type ID.
   *
   * @var string
   */
  protected $entity_type;

  /**
   * The bundle.
   *
   * @var string
   */
  protected $bundle;

  /**
   * Field list for regular hours.
   *
   * @var array
   */
  protected $regular = [];

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return (string) $this->t('Map for @entityType:@bundle', [
      '@entityType' => $this->entity_type,
      '@bundle' => $this->bundle,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function id(): ?string {
    if ($this->entity_type && $this->bundle) {
      return sprintf('%s.%s', $this->getMapEntityType(), $this->getMapBundle());
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getMapEntityType(): string {
    return $this->entity_type;
  }

  /**
   * {@inheritdoc}
   */
  public function getMapBundle(): string {
    return $this->bundle;
  }

  /**
   * {@inheritdoc}
   */
  public function getRegularFields(): array {
    return $this->regular;
  }

  /**
   * {@inheritdoc}
   */
  public function setRegularFields(array $regularFields) {
    $this->regular = $regularFields;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies(): void {
    parent::calculateDependencies();
    foreach ($this->regular as $regular) {
      $this->addDependency('config', sprintf('field.field.%s.%s.%s', $this->entity_type, $this->bundle, $regular['field_name']));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onDependencyRemoval(array $dependencies): bool {
    $changed = parent::onDependencyRemoval($dependencies);

    // Remove references to field configs if they are deleted.
    foreach ($dependencies['config'] as $entity) {
      if ($entity instanceof FieldConfigInterface) {
        foreach ($this->regular as $k => $regular) {
          if ($regular['field_name'] === $entity->getName()) {
            unset($this->regular[$k]);
            $changed = TRUE;
          }
        }
      }
    }

    return $changed;
  }

  /**
   * {@inheritdoc}
   */
  protected function invalidateTagsOnSave($update): void {
    parent::invalidateTagsOnSave($update);
    Cache::invalidateTags([static::CACHE_TAG_ALL]);
  }

  /**
   * {@inheritdoc}
   */
  protected static function invalidateTagsOnDelete(EntityTypeInterface $entity_type, array $entities): void {
    parent::invalidateTagsOnDelete($entity_type, $entities);
    Cache::invalidateTags([static::CACHE_TAG_ALL]);
  }

}
