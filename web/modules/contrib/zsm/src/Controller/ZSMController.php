<?php
/**
 * @file
 */

namespace Drupal\zsm\Controller;

use Drupal\Core\Controller\ControllerBase;
//use Drupal\Core\Cache\CacheableJsonResponse
use Drupal\Core\Cache\CacheableMetadata;
use \Drupal\zsm\Entity\ZSMCore;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Yaml\Yaml;

/**
 * Class ZSMController
 *
 * Provides the route controller for zsm.
 */
class ZSMController extends ControllerBase
{
  /**
   * @param $uuid
   * @return JsonResponse
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function get_settings_json($uuid) {
    $resp = $this->get_settings_data($uuid);
    if ($resp) {
      // Add the zsm_core cache tag so the endpoint results will update when nodes are
      // updated.
      $cache_metadata = new CacheableMetadata();
      $cache_metadata->setCacheTags(['zsm_core']);

      // Create the JSON response object and add the cache metadata.
      //$response = new CacheableJsonResponse($response_array);
      //$response->addCacheableDependency($cache_metadata);
      $response = new JsonResponse($resp);

      return $response;
    }
    else {
      $response = new JsonResponse([]);
      return $response;
    }
  }

  /**
   * @param $uuid
   * @return array|bool
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function get_settings_data($uuid)
  {
    // Check the uuid
    $check = FALSE;
    if (is_string($uuid) && (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $uuid) === 1)) {
      $check = TRUE;
    }
    if ($check) {
      $n = \Drupal::entityTypeManager()->getStorage('zsm_core')->loadByProperties(['uuid' => $uuid]);
      $id = array_keys($n)[0];
      if ($id) {
        $core = ZSMCore::load($id);

        // First see if we have a custom field
        if ($core->hasField('field_custom_yaml') && !empty($core->get('field_custom_yaml')->getValue())) {
          $yaml = Yaml::parse($core->get('field_custom_yaml')->getValue()[0]['value']);
          return $yaml;
        }
        else {
          // Get the core settings
          $data = ['settings' => [], 'conf' => []];
          $settings_map = \Drupal::service('entity_field.manager')->getFieldDefinitions('zsm_core', 'zsm_core');
          // Clean out DB items that do not go into the settings
          $settings_map = array_keys($settings_map);
          $exclude = ['id', 'uuid', 'title', 'user_id', 'created', 'changed'];
          $settings_map = array_diff($settings_map, $exclude);
          foreach ($settings_map as $key) {
            if (strpos($key, 'path') !== FALSE) {
              if ($val = $core->get($key)->getValue()) {
                if (isset($val[0]['value'])) {
                  $val = $val[0]['value'];
                  $k = str_replace('path_', '', $key);
                  $data['settings']['user_data'][$k] = $val;
                }
              }

            }
            else if (strpos($key, 'field_zsm_enabled_plugins') !== FALSE) {
              if ($vals = $core->get($key)->getValue()) {
                foreach($vals as $key => $val) {
                  $plug = \Drupal::entityTypeManager()->getStorage($val['target_type'])->load($val['target_id']);
                  $plug_data = $plug->getZSMPluginData();
                  $data['conf']['plugins'][$plug_data['class']] = $plug->getZSMPluginSettings();
                }
              }
            }
            else {
              if ($val = $core->get($key)->getValue()) {
                if (isset($val[0]['value'])) {
                  $val = $val[0]['value'];
                  $data['settings'][$key] = $val;
                }
              }
            }
          }
          return $data;
        }
      }
      else {
        return FALSE;
      }
    }
    else {
      return FALSE;
    }
  }

  /**
   * @param $zsm_core
   * @param $entity_type
   * @param $entity_id
   * @return RedirectResponse
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function remove_plugin($zsm_core, $entity_type, $entity_id) {
    $core = \Drupal::entityTypeManager()->getStorage('zsm_core')->load($zsm_core);
    $field_current = $core->get('field_zsm_enabled_plugins')->getValue();
    $field_new = array();
    foreach ($field_current as $fc) {
      if ($fc['target_id'] !== $entity_id || $fc['target_type'] !== $entity_type) {
        $field_new[] = $fc;
      }
    }
    $core->field_zsm_enabled_plugins->setValue($field_new);
    $core->save();

    drupal_set_message('Plugin was removed');

    $response = new RedirectResponse($_SERVER['HTTP_REFERER']);
    return $response;
  }
}