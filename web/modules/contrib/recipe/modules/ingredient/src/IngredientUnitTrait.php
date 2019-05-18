<?php

namespace Drupal\ingredient;

/**
 * Provides functions for accessing Ingredient unit configuration.
 */
trait IngredientUnitTrait {

  /**
   * Returns an array of units from configuration.
   *
   * @param string[] $sets_to_get
   *   An array of set id strings.
   *
   * @return string[]
   *   An array of units.
   */
  protected function getConfiguredUnits(array $sets_to_get = []) {
    // The field settings will set disabled set values to 0 in configuration.
    // Filter out any disabled values.  This prevents custom unit sets with an
    // ID of '0' and another solution may have to be found eventually.
    $sets_to_get = array_filter($sets_to_get);

    $unit_sets = \Drupal::config('ingredient.units')->get('unit_sets');

    $units = [];
    foreach ($unit_sets as $set_id => $set) {
      // Verify that the set contains an array of units.
      if (empty($set['units']) || !is_array($set['units'])) {
        continue;
      }
      // Skip the set if it's not in the $sets_to_get.
      if (!empty($sets_to_get) && is_array($sets_to_get) && !in_array($set_id, $sets_to_get)) {
        continue;
      }

      $units = array_merge($units, $set['units']);
    }
    return $units;
  }

  /**
   * Sorts an array of units by the name element.
   *
   * @param array $units
   *   An array containing a 'name' element.
   *
   * @return array
   *   The sorted array of units.
   */
  protected function sortUnitsByName(array $units) {
    uasort($units, function ($a, $b) {
      return strcmp($a['name'], $b['name']);
    });
    return $units;
  }

  /**
   * Returns options for a unit select form element.
   *
   * @todo The blank option should be created as the #empty_option in the
   *   individual select elements.  Unfortunately, the default_unit element in
   *   the field settings form uses a #process function to set its options which
   *   seems to overwrite the #empty_option.  Even setting the #empty_option
   *   during the #process step doesn't seem to work.  This seems like a
   *   possible core bug and will probably have to be fixed there before we can
   *   complete this task.
   *
   * @param array $units
   *   An array of units.
   *
   * @return array
   *   An array of unit key/value pairs for use as select form element options.
   */
  protected function createUnitSelectOptions(array $units = []) {
    // Put in a blank so non-matching units will not validate and save.
    $options = ['' => ''];

    foreach ($units as $unit_key => $unit) {
      $text = $unit['name'];
      if (!empty($unit['abbreviation'])) {
        $text .= ' (' . $unit['abbreviation'] . ')';
      }
      $options[$unit_key] = $text;
    }
    return $options;
  }

  /**
   * Returns options for a unit set select form element.
   *
   * @return array
   *   An array of all unit set names with unit names, keyed by set id.
   */
  protected function getUnitSetOptions() {
    $unit_sets = \Drupal::config('ingredient.units')->get('unit_sets');

    $set_names = [];
    foreach ($unit_sets as $key => $set) {
      $set_name = $set['name'];

      // Append a list of the unit names in the set, in parenthesis.
      $unit_names = [];
      foreach ($set['units'] as $unit) {
        $unit_names[] = $unit['name'];
      }
      if (!empty($unit_names)) {
        $set_name .= ' <em>(' . implode(', ', $unit_names) . ')</em>';
      }

      $set_names[$key] = $set_name;
    }
    return $set_names;
  }

}
