<?php

namespace Drupal\entity_type_clone\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class EntityTypeCloneController.
 *
 * @package Drupal\entity_type_clone\Controller
 */
class EntityTypeCloneController extends ControllerBase {

  /**
   * Replaces string values recursively in an array.
   *
   * @param string $find
   *   The string to find in the array values.
   * @param string $replace
   *   The replacement string.
   * @param array $arr
   *   The array to search.
   * @return array $newArray
   *   The array with values replaced.
   */
  public static function arrayReplace($find, $replace, $arr) {
    $newArray = array();
    foreach ($arr as $key => $value) {
      if (is_array($value)) {
        $newArray[$key] = self::arrayReplace($find, $replace, $value);
      }
      else {
        $newArray[$key] = str_replace($find, $replace, $value);
      }
    }
    return $newArray;
  }

  public static function copyFieldDisplay($display, $mode, $data) {
    // Prepare the storage string
    $storage = 'entity_' . $display . '_display';
    //Get the source field name.
    $sourceFieldName = $data['field']->getName();
    //Get the source form display
    $sourceDisplay = \Drupal::entityTypeManager()->getStorage($storage)->load($data['values']['show']['entity_type'] . '.' . $data['values']['show']['type'] . '.' . $mode)->toArray();
    //Prepare the target form display
    $targetDisplay = EntityTypeCloneController::arrayReplace(
        $data['values']['show']['type'], $data['values']['clone_bundle_machine'], $sourceDisplay
    );
    unset($targetDisplay['uuid']);
    unset($targetDisplay['_core']);
    //Save the target display
    if ($display == 'form') {
      //Save the form display
      $displayConfig = \Drupal::configFactory()
        ->getEditable('core.' . $storage . '.' . $data['values']['show']['entity_type'] . '.' . $data['values']['clone_bundle_machine'] . '.' . $mode)
        ->setData($targetDisplay)
        ->save();
    }
    else if ($display == 'view') {
      //Save the view display
      $entityDisplay = entity_get_display($data['values']['show']['entity_type'], $data['values']['clone_bundle_machine'], $mode);
      if (isset($targetDisplay['content'][$sourceFieldName])) {
        $entityDisplay->setComponent($sourceFieldName, $targetDisplay['content'][$sourceFieldName]);
      }
      //Hide the field if needed
      if (isset($targetDisplay['hidden'][$sourceFieldName]) && (int) $targetDisplay['hidden'][$sourceFieldName] == 1) {
        $entityDisplay->removeComponent($sourceFieldName);
      }
      //Save the display
      $entityDisplay->save();
    }
    return new JsonResponse(t('Success'));
  }

}
