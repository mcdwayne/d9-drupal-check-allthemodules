<?php

namespace Drupal\ingredient;

use Drupal\views\EntityViewsData;

/**
 * Provides the views data for the ingredient entity type.
 */
class IngredientViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['ingredient_field_data']['table']['base']['access query tag'] = 'ingredient_access';
    $data['ingredient_field_data']['table']['wizard_id'] = 'ingredient';

    $data['ingredient_field_data']['id']['argument'] = [
      'id' => 'ingredient_id',
      'name field' => 'name',
      'numeric' => TRUE,
      'validate type' => 'id',
    ];

    return $data;
  }

}
