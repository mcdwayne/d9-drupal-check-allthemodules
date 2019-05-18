<?php

namespace Drupal\extensions_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Access\AccessResult;

class ExtensionsApiController extends ControllerBase {

  /**
   * Access check checking the user's token. A user with
   * uid 1 always has permission.
   * 
   * @return \Drupal\Core\Access\AccessResult
   *   Whether the access is allowed or forbidden.
   */
  public function permission() {
    $arg = \Drupal::request()->request->get('token');
    $uid = \Drupal::currentUser()->id();

    // Always allow 1-user.
    if (!empty($uid) && (int) $uid === 1) {
       return AccessResult::allowed();
    }

    // Check if parameter and token are set.
    $token = \Drupal::service('config.factory')
      ->getEditable('extensions_api.settings')
      ->get('token');
    if (empty($arg) || empty($token)) {
      return AccessResult::forbidden();
    }
    
    // Check if parameter and token are equal.
    if ($arg === $token) {
      return AccessResult::allowed();
    }
    
    // When none of the above applies, deny access.
    return AccessResult::forbidden();
  }
  
  /**
   * Renders the JSON output.
   * 
   * @param string $category
   *   The type to give results for (all, core, module or theme).
   * 
   * @return JsonResponse
   *   A list of extensions in a JSON format.
   */
  public function showList($category) {
    $types = [];
    if ($category === 'all') {
      $types = [
        'core', 'module', 'theme',
      ];
    }
    else {
      $types = [$category];
    }
    
    $list = $this->getExtensions($types);
    return new JsonResponse($list, 200, ['Content-Type'=> 'application/json']);
  }

  /**
   * Get a list of extentions by category.
   * 
   * @param array $category
   *   An array of categories (core | module | theme)
   * 
   * @return array
   *   A list of available extensions.
   */
  private function getExtensions(array $category) {
    $update_data = [];
    
    if ( \Drupal::moduleHandler()->moduleExists('update')) {
      $available = update_get_available(TRUE);
      module_load_include('inc', 'update', 'update.compare');
      $update_data = update_calculate_project_data($available);
    }
  
    $list = [];
    if (in_array('core', $category)) {
      $status = 'up to date';
      if (!empty($update_data['drupal'])) {
        $status = $this->getUpdateStatus($update_data['drupal']['status']);
      }
      $list['core'] = [   
        'type' => 'core',
        'name' => 'core',
        'title' => 'Core',
        'description' => 'The Drupal core',
        'version' => \Drupal::VERSION,
        'package' => 'Core',
        'active' => 1,
        'update_status' => $status,
      ];
    }
    
    $mods = [];
    if (in_array('module', $category)) {
      $mods += system_rebuild_module_data();
    }
    if (in_array('theme', $category)) {
      $mods += \Drupal::service('theme_handler')->rebuildThemeData();
    }

    foreach ($mods as $key => $row) {
      $status = 'up to date';
      if (!empty($update_data[$key])) {
        $status = $this->getUpdateStatus($update_data[$key]['status']);
      }
      
      $type = $this->getType($row->info);

      $list[$key] = [
        'type' => $type,
        'name' => $key,
        'title' => $row->info['name'],
        'description' => $row->info['description'],
        'version' => (isset($row->info['version']) ? $row->info['version'] : ''),
        'package' => (isset($row->info['package']) ? $row->info['package'] : ''),
        'active' => $row->status,
        'update_status' => $status,
      ];
    }
    return $list;
  }

  /**
   * A helper function to get the type by info array.
   *
   * @param array $info
   *   An array containing the info.
   *
   * @return string
   *   The extension type ([core|contrib|custom]-[theme|module]).
   */
  private function getType($info) {
    if (empty($info)) {
      return 'error';
    }
    $value = 'contrib-' . $info['type'];
    if (empty($info['version']) && empty($info['datestamp'])) {
      $value = 'custom-' . $info['type'];
    }
    elseif (isset($info['project']) && $info['project'] === 'drupal') {
      $value = 'core-' . $info['type'];
    }
    return $value;
  }

  /**
    * Get update status by type.
    * 
    * @param integer $status
    *   The status value.
    * 
    * @return string
    *   The status message.
    */
   private function getUpdateStatus($status) {
     switch ($status) {
       case UPDATE_NOT_SECURE:
       case UPDATE_REVOKED:
         $type = 'security';
         break;

       case UPDATE_NOT_SUPPORTED:
         $type = 'unsupported';
         break;

       case UPDATE_UNKNOWN:
       case UPDATE_NOT_FETCHED:
       case UPDATE_NOT_CHECKED:
       case UPDATE_NOT_CURRENT:
         $type = 'recommended';
         break;

       case UPDATE_CURRENT:
         $type = 'up to date';
         break;

       default:
         // Jump out of the switch and onto the next project in foreach.
         continue;
     }
     return $type;
   }
}
