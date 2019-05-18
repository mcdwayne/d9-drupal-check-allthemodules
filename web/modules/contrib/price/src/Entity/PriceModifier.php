<?php

namespace Drupal\price\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines the entity class.
 *
 * @ConfigEntityType(
 *   id = "price_modifier",
 *   label = @Translation("Price Modifier"),
 *   label_collection = @Translation("Price Modifiers"),
 *   label_singular = @Translation("price modifier"),
 *   label_plural = @Translation("price modifiers"),
 *   label_count = @PluralTranslation(
 *     singular = "@count price modifier",
 *     plural = "@count price modifiers",
 *   ),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\price\Form\PriceModifierForm",
 *       "edit" = "Drupal\price\Form\PriceModifierForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *     "list_builder" = "Drupal\price\PriceModifierListBuilder",
 *   },
 *   admin_permission = "administer price",
 *   config_prefix = "price_modifier",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "id",
 *     "label"
 *   },
 *   links = {
 *     "add-form" = "/admin/config/price/price-modifiers/add",
 *     "edit-form" = "/admin/config/price/price-modifiers/{price_modifier}",
 *     "delete-form" = "/admin/config/price/price-modifiers/{price-modifier}/delete",
 *     "collection" = "/admin/config/price/price-modifiers"
 *   }
 * )
 */
class PriceModifier extends ConfigEntityBase implements PriceModifierInterface {

  /**
   * The machine name of this entity type.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the entity type.
   *
   * @var string
   */
  protected $label;

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);

    // Clear the entity type cache to reflect the removal.
    $storage->resetCache(array_keys($entities));
  }

}
