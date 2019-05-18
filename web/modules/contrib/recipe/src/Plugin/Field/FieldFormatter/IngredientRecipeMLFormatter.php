<?php

namespace Drupal\recipe\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ingredient\IngredientUnitTrait;
use Drupal\ingredient\Plugin\Field\FieldFormatter\IngredientFormatter;

/**
 * Plugin implementation of the 'ingredient_recipeml' formatter.
 *
 * @FieldFormatter(
 *   id = "ingredient_recipeml",
 *   module = "recipe",
 *   label = @Translation("RecipeML"),
 *   field_types = {
 *     "ingredient"
 *   }
 * )
 */
class IngredientRecipeMLFormatter extends IngredientFormatter {

  use IngredientUnitTrait;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    // Remove the entity link element from the parent's settings.
    $settings = parent::defaultSettings();
    unset($settings['link']);
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    // Remove the entity link element from the parent's settings form.
    $element = parent::settingsForm($form, $form_state);
    unset($element['link']);
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    // Remove the entity link information from the parent's settings summary.
    $summary = parent::settingsSummary();
    unset($summary[2]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $fraction_format = $this->getSetting('fraction_format');
    $unit_list = $this->getConfiguredUnits();
    $elements = [];

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {
      // Sanitize the name and note.
      $name = Xss::filter($entity->label(), []);
      $note = Xss::filter($items[$delta]->note, []);

      if ($items[$delta]->quantity > 0) {
        $formatted_quantity = ingredient_quantity_from_decimal($items[$delta]->quantity, $fraction_format);
      }
      else {
        $formatted_quantity = '&nbsp;';
      }

      // Print the unit unless it has no abbreviation. Those units do not get
      // printed in any case.
      $unit_name = '';
      $unit_abbreviation = '';
      $unit = isset($unit_list[$items[$delta]->unit_key]) ? $unit_list[$items[$delta]->unit_key] : [];
      if (!empty($unit['abbreviation'])) {
        $unit_name = $items[$delta]->quantity > 1 ? $unit['plural'] : $unit['name'];
        $unit_abbreviation = $unit['abbreviation'];
      }

      $elements[$delta] = [
        '#theme' => 'ingredient_recipeml_formatter',
        '#name' => $name,
        '#quantity' => $formatted_quantity,
        '#unit_name' => $unit_name,
        '#unit_abbreviation' => $unit_abbreviation,
        '#unit_display' => $this->getSetting('unit_abbreviation'),
        '#note' => $note,
      ];
    }
    return $elements;
  }

}
