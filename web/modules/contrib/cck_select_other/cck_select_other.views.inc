<?php
/**
 * @file
 * Contains \cck_select_other_views_data_alter().
 */

/**
 * Implements of hook_views_data_alter().
 */
function cck_select_other_views_data_alter(array &$data) {

  try {
    // Get all the displays because Drupal.
    $displays = \Drupal::entityManager()->getStorage('entity_form_display')->loadMultiple();

    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $display */
    foreach ($displays as $display) {
      // Get all the components on the display, which will allow to load the
      // renderer for each given field name and thus get the widget plugin id.
      foreach ($display->getComponents() as $field_name => $configuration) {
        $widget = $display->getRenderer($field_name);
        if (isset($widget)) {
          $widget_id = $widget->getPluginId();

          if ($widget_id === 'cck_select_other') {
            $table = $display->getTargetEntityTypeId() . '__' . $field_name;
            $field = $field_name . '_value';
            $data[$table][$field]['filter']['id'] = 'select_other';
          }
        }
      }
    }
  }
  catch (\Exception $e) {
    // @todo catch this.
    throw $e;
  }
}

