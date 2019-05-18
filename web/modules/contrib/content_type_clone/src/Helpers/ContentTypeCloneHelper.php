<?php
namespace Drupal\content_type_clone\Helpers;

use Drupal\node\Entity\NodeType;
use Drupal\node\Entity\Node;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Class ContentTypeCloneHelper.
 *
 * @package Drupal\content_type_clone\Helpers
 */
class ContentTypeCloneHelper {

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
  public static function replaceInArray($find, $replace, $arr)
  {
    $newArray = array();
    foreach ($arr as $key => $value) {
      if (is_array($value)) {
        $newArray[$key] = self::replaceInArray($find, $replace, $value);
      }
      else {
        $newArray[$key] = str_replace($find, $replace, $value);
      }
    }

    return $newArray;
  }

  public static function copyFieldDisplay($display, $mode, $data)
  {
    // Prepare the storage string
    $storage = 'entity_' . $display . '_display';

    //Get the source field name.
    $sourceFieldName = $data['field']->getName();

    //Get the source form display
    $sourceDisplay = \Drupal::entityTypeManager()->getStorage($storage)
      ->load('node.' . $data['values']['source_machine_name'] . '.' . $mode)
      ->toArray();

    //Prepare the target form display
    $targetDisplay = ContentTypeCloneHelper::replaceInArray(
      $data['values']['source_machine_name'],
      $data['values']['target_machine_name'],
      $sourceDisplay
    );
    unset($targetDisplay['uuid']);
    unset($targetDisplay['_core']);

    //Save the target display
    if ($display == 'form') {
      //Save the form display
      $displayConfig = \Drupal::configFactory()
        ->getEditable('core.' . $storage . '.node.' . $data['values']['target_machine_name'] . '.' . $mode)
        ->setData($targetDisplay)
        ->save();
    }
    else if ($display == 'view') {
      //Save the view display
      $entityDisplay = entity_get_display('node', $data['values']['target_machine_name'], $mode);
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
  }  
}