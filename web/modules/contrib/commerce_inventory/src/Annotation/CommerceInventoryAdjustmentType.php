<?php

namespace Drupal\commerce_inventory\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Inventory Adjustment type item annotation object.
 *
 * @see \Drupal\commerce_inventory\InventoryAdjustmentTypeManager
 * @see plugin_api
 *
 * @Annotation
 */
class CommerceInventoryAdjustmentType extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The preposition used to describe how the adjustment affects the location.
   *
   * This preposition is for use in a sentence. Defaults to 'at'.
   *
   * Example:
   *   - Untranslated: "Increase 10 @adjustment_preposition Location Name"
   *   - Translated: "Increase 10 at Location Name"
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label_preposition = NULL;

  /**
   * The preposition describing how the adjustment affects the related location.
   *
   * This preposition is for use in a sentence.
   *
   * Example:
   *   - Untranslated: "Move 10 from Location Name @related_preposition Related"
   *   - Translated: "Move 10 from Location Name to Related"
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label_related_preposition = NULL;

  /**
   * The action sentence used for adjustment explanation.
   *
   * Contextual options:
   *   - "@item": The Inventory Item label.
   *   - "@location": The translated Location label.
   *   - "@purchasable_entity": The translated Purchasable Entity label.
   *   - "@related_location": The translated related-Location label.
   * Adjustment Type options:
   *   - "@adjustment_verb": The adjustment verb.
   *   - "@adjustment_preposition": The adjustment preposition.
   *   - "@related_preposition": The related adjustment preposition, if
   *     adjustment type has a related adjustment.
   *
   * Example (no related adjustment):
   *   - Untranslated: "Increase @item at @location"
   *   - Translated: "Increase items at Location Name"
   * Example (with related adjustment):
   *   - Untranslated: "Move @item_count from @location to @related_location"
   *   - Translated: "Move 10 items from Location to Related Location"
   *
   * @var string
   */
  public $label_sentence_template = '@adjustment_verb @purchasable_entity @adjustment_preposition @location';

  /**
   * The verb describing the adjustment.
   *
   * Used in a sentence. Defaults to Adjustment Type label.
   *
   * Example:
   *   - Untranslated: "@adjustment_verb 10 at Location Name"
   *   - Translated: "Increase 10 at Location Name"
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label_verb;

  /**
   * A related Adjustment type ID that to be paired with this one.
   *
   * @var string
   */
  public $related_adjustment_type = '';

}
