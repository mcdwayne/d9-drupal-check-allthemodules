<?php

namespace Drupal\fasttoggle\Controller;

require_once __DIR__ . '/../../fasttoggle.inc';

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route controller for Fasttoggle.
 */
class FasttoggleController extends ControllerBase {

  /**
   * @var \Drupal\fasttoggle\SettingObjectPluginManager
   *   The plugin manager for objects that have Fasttogglable settings.
   */
  private $objectManager;

  /**
   * @var \Drupal\fasttoggle\SettingGroupPluginManager
   *   The plugin manager for grouping of settings.
   */
  private $settingGroupManager;

  /**
   * @var \Drupal\fasttoggle\SettingPluginManager
   *   The plugin manager for classes that implement toggling settings on
   *   objects.
   */
  private $settingManager;

  /**
   * Constructor
   */
  public function __construct() {
    $this->objectManager = \Drupal::service('plugin.manager.fasttoggle.setting_object');
    $this->settingGroupManager = \Drupal::service('plugin.manager.fasttoggle.setting_group');
    $this->settingManager = \Drupal::service('plugin.manager.fasttoggle.setting');
  }

  /**
   * Get Fasttoggle config.
   *
   * @return array
   *   Fasttoggle configuration array.
   */
  public static function getConfig() {
    return \Drupal::config('fasttoggle.settings');
  }

  /**
   * Get an array of sitewide settings.
   *
   * @return array
   *   An array of objects and settings for building the form and saving
   *   settings.
   */
  public function getSitewideSettingsInfo() {
    $objectSettings = $this->settingsManager->getDefinitions();

    $objects = $this->objectManager->getDefinitions();
    foreach ($objects as $type => $object) {
      $plugin = $this->objectManager->createInstance($type);
      $objectSettings[$type] += $plugin->sitewideSettings();
    }

    return $objectSettings;
  }

  /**
   * Get an object manager for an object.
   *
   * @param object
   *   The object to use.
   *
   * @return \Drupal\fasttoggle\Plugin\SettingObject
   *   The matching object.
   */
  public function getObjectManager($instance) {
    $objects = $this->objectManager->getDefinitions();
    foreach ($objects as $type => $object) {
      $plugin = $this->objectManager->createInstance($type);
      if ($plugin->objectMatches($instance)) {
        $plugin->setObject($instance);
        return $plugin;
      }
    }

    return null;
  }

  /**
   * Get the group and setting for a field name.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface
   *   The field with the value to be toggled.
   * @return array
   *   The group and setting - NULL if not found.
   */
  public function groupAndSettingFromFieldName($definition) {
    $settingPlugin = $this->settingManager->match($definition);
    if ($settingPlugin) {
      $group = $settingPlugin->getPluginDefinition()['group'];
      $groupPlugin = $this->settingGroupManager->createInstance($group);
    }
    else {
      $groupPlugin = NULL;
    }
    return array($groupPlugin, $settingPlugin);
  }

  /**
   * Toggle a setting.
   *
   * @param $request
   *   The request object.
   */
  public function toggle(Request $request) {
    // I don't like this - I should be able to just specify args in the routing
    // like the docs say and put them as args to this function. It wouldn't work
    // however...
    // We need to do our own handling of the path if we're coming via a view.
      $parts = explode('/', $request->getPathInfo());

    if (count($parts) != 4 && count($parts) != 5) {
      $response = new Response();
      $response->setContent('Incorrect number of parameters');
      $response->setStatusCode(400);
      return $response;
    }

    // Use the token to look up cached parameters.
    // Fasttoggle Parameters
    // - Entity type
    // - Entity ID
    // - Setting Group
    // - Setting
    // - Language code
    // - Base formatter
    // Formatter parameters
    // - Plugin definition
    // - Field definition
    // - Settings
    // - Label
    // - View mode
    // - Third party settings
    $cid = $parts[3];
    $newValue = count($parts) == 5 ? NULL : $parts[4];

    $cached = \Drupal::cache()->get('fasttoggle-' . $cid);
    if (!$cached) {
      $response = new Response();
      $response->setContent('Unrecognised setting hash.');
      $response->setStatusCode(400);
      return $response;
    }

    $entity_type = $cached->data['object_type'];
    $entity_id = $cached->data['object_id'];
    $group = $cached->data['group'];
    $setting = $cached->data['setting'];

    // Validate parameters.
    $this->objectManager = \Drupal::service('plugin.manager.fasttoggle.setting_object');
    $objects = $this->objectManager->getDefinitions();
    if (!$objects[$entity_type]) {
        $response = new Response();
        $response->setContent('Unrecognised entity type.');
        $response->setStatusCode(400);
        return $response;
    }

    $controller = \Drupal::entityManager()->getStorage($entity_type);
    $entity = $controller->load($entity_id);
    if (!$entity) {
      $response = new Response();
      $response->setContent('Invalid entity ID.');
      $response->setStatusCode(400);
      return $response;
    }

    $settings = $this->settingManager->getDefinitions();
    if (empty($settings[$entity_type]) || empty($settings[$entity_type][$group]) ||
      empty($settings[$entity_type][$group][$setting])) {
      return;
    }

    // Check access to the setting group.
    $plugin = $this->settingManager->createInstance($setting);
    $plugin->setObject($entity);
    $plugin->setField($cached->data['field_definition']);

    // Check permissions.
    if (!$plugin->mayEdit()->allowed()) {
      $response = new Response();
      $response->setContent('No edit permission.');
      $response->setStatusCode(400);
      return $response;
    }

    // Apply the change - will need modifying for roles.
    //$instance = array_search($setting, $this->attributes);
    $instance = 0;

    if (is_null($newValue)) {
      $plugin->nextValue($instance);
    }
    else {
      $plugin->setValue($instance, $newValue);
    }

    $plugin->save();

    $renderer = \Drupal::service('renderer');
    $delta = $plugin->get_field();
    $render_array = $plugin->formatter($delta, $cached->data);
    $html = $renderer->render($render_array);

    // Return Ajax for the modified content and modified link.
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('#fasttoggle-' . $cid, $html . ""));
    return $response;
  }
}
