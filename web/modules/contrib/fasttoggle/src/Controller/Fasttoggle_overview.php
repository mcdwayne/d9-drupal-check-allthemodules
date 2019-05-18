<?php

namespace Drupal\fasttoggle\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Ajax\CommandInterface;

/**
 * Route controller for Fasttoggle.
 */
class Fasttoggle implements ContainerInjectionInterface {

  /**
   * The database connection
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The object plugin manager.
   *
   * We use this to get all of the object plugins.
   *
   * @var \Drupal\fasttoggle\ObjectPluginManager
   */
  protected $objectPluginManager;

  /**
   * The setting plugin manager.
   *
   * We use this to get all of the setting plugins.
   *
   * @var \Drupal\fasttoggle\SettingPluginManager
   */
  protected $settingPluginManager;

  /**
   * Constructs a \Drupal\fasttoggle\Controller\FasttoggleController object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\fasttoggle\ObjectPluginManager $object_manager
   *   The object plugin manager service. We're injecting this service so that
   *   we can use it to access the object plugins.
   * @param \Drupal\fasttoggle\SettingPluginManager $setting_manager
   *   The setting plugin manager service. We're injecting this service so that
   *   we can use it to access the setting plugins.
   */
  public function __construct(Connection $database, ObjectPluginManager $object_manager, SettingPluginManager $setting_manager) {
    $this->database = $database;
    $this->objectPluginManager = $object_manager;
    $this->settingPluginManager = $setting_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('database'));
  }

  /**
   * Displays a page with an overview of our plugin type and plugins.
   *
   * Lists all the managed object and their settings' plugin definitions by
   * using methods on the
   * \Drupal\fasttoggle\ObjectPluginManager and SettingPluginManger classes.
   * Lists out the description for each object and setting found by invoking
   * methods defined on the plugins themselves.  You can find the plugins we
   * have defined in the
   * \Drupal\fasttoggle\Plugin\Object and Setting namespaces.
   */
  public function description() {
    $build = [];

    $build['intro'] = [
      '#markup' => t("This page lists Fasttoggle plugins and objects that may be managed."),
    ];

    // Get the list of all the Fasttoggle plugins defined on the system from the
    // plugin manager. Note that at this point, what we have is *definitions* of
    // plugins, not the plugins themselves.
    $object_plugin_definitions = $this->objectManager->getDefinitions();

    // Let's output a list of the plugin definitions we now have.
    $items = [];
    foreach ($object_plugin_definitions as $object_plugin_definition) {
      $items[] = t("!id (calories: !calories, foobar: !foobar )", [
        '!id' => $object_plugin_definition['id'],
        '!calories' => $object_plugin_definition['calories'],
        '!foobar' => $object_plugin_definition['foobar'],
      ]);
    }

    // Add our list to the render array.
    $build['plugin_definitions'] = [
      '#theme' => 'item_list',
      '#title' => 'Object type plugin definitions',
      '#items' => $items,
    ];

    // If we want just a single plugin definition, we can use getDefinition().
    // This requires us to know the ID of the plugin we want. This is set in the
    // annotation on the plugin class: see ExampleHamSandwich.
    // $ham_object_plugin_definition = $this->sandwichManager->getDefinition('ham_sandwich');

    // To get an actual plugin, we call createInstance() on the plugin manager,
    // passing the ID of the plugin we want to load. Let's output a list of the
    // actual plugins.
    $items = [];
    // The array of plugin definitions is keyed by plugin id, so we can just use
    // that to load our plugins.
    foreach ($object_plugin_definitions as $plugin_id => $object_plugin_definition) {
      // We now have a plugin! From here on it can be treated just as any other
      // object: have its properties examined, methods called, etc.
      $plugin = $this->objectTypeManager->createInstance($plugin_id, ['of' => 'configuration values']);
      $items[] = $plugin->description();
    }

    $build['plugins'] = [
      '#theme' => 'item_list',
      '#title' => 'Object plugins',
      '#items' => $items,
    ];

    return $build;
  }
}
