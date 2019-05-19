<?php

namespace Drupal\visualn\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the VisualN style entity.
 *
 * @ConfigEntityType(
 *   id = "visualn_style",
 *   label = @Translation("VisualN Style"),
 *   handlers = {
 *     "list_builder" = "Drupal\visualn\VisualNStyleListBuilder",
 *     "form" = {
 *       "add" = "Drupal\visualn\Form\VisualNStyleForm",
 *       "edit" = "Drupal\visualn\Form\VisualNStyleForm",
 *       "delete" = "Drupal\visualn\Form\VisualNStyleDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\visualn\VisualNStyleHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "visualn_style",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "drawer_id" = "drawer_id",
 *     "drawer_type" = "drawer_type",
 *     "drawer_type_prefix" = "drawer_type_prefix",
 *     "drawer_config" = "drawer_config"
 *   },
 *   links = {
 *     "canonical" = "/admin/visualn/styles/manage/{visualn_style}",
 *     "add-form" = "/admin/visualn/styles/add",
 *     "edit-form" = "/admin/visualn/styles/manage/{visualn_style}/edit",
 *     "delete-form" = "/admin/visualn/styles/manage/{visualn_style}/delete",
 *     "collection" = "/admin/visualn/styles"
 *   }
 * )
 */
class VisualNStyle extends ConfigEntityBase implements VisualNStyleInterface {

  /**
   * The VisualN style ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The VisualN style label.
   *
   * @var string
   */
  protected $label;

  /**
   * The VisualN style drawer ID.
   *
   * @var string
   */
  protected $drawer_id;

  /**
   * The VisualN drawer type (base|sub).
   *
   * @var string
   */
  protected $drawer_type;

  /**
   * The VisualN drawer type prefix (base|sub).
   *
   * The property is added for consistency (see visualn_style.schema.yml)
   *
   * @var string
   */
  protected $drawer_type_prefix;

  /**
   * The VisualN style drawer config.
   *
   * @var array
   */
  protected $drawer_config = [];

  /**
   * The VisualN style specific drawer plugin.
   *
   * @var \Drupal\visualn\Core\DrawerInterface
   */
  protected $drawer_plugin;

  /**
   * {@inheritdoc}
   */
  public function getDrawerId() {
    return $this->drawer_id ?: '';
  }

  /**
   * {@inheritdoc}
   *
   * @todo: add to interface
   * @todo: maybe rename the method
   */
  public static function getSubDrawerWrapperPluginArguments($visualn_drawer_id) {
    $visualn_drawer = \Drupal::service('entity_type.manager')->getStorage('visualn_drawer')->load($visualn_drawer_id);

    $base_drawer_id = $visualn_drawer->getBaseDrawerId();
    $drawer_config = $visualn_drawer->getDrawerConfig();

    // @todo: get drawer wrapper id form drawer or its manager annotation
    //$wrapper_drawer_id = 'visualn_default_drawer_wrapper';
    $wrapper_drawer_id = \Drupal::service('plugin.manager.visualn.drawer')->getDefinition($base_drawer_id)['wrapper_drawer_id'];

    // @todo: is that ok (from performance point of view) to get modifiers that way
    //    and pass so to the $wrapper_drawer_config?
    //    also this method can be called multiple times
    $modifiers = [];
    foreach ($visualn_drawer->getModifiers() as $modifier) {
      $modifiers[$modifier->getUuid()] = $modifier;
    }

    // @todo: here should go all other subdrawer info such as modifiers info, maybe subdrawer id etc.
    //    also consider that subdrawers not always really need a wrapper (e.g. when there are no modifiers attach)
    //    or even when they have modifiers that don't really need wrapping (some modifiers could change drawers behaiour at
    //    config level via drawer_config, the so-called config modifiers/generators)
    // @todo: Modifiers could have a special method, that allows the to register themselves
    //    for the wrapper. If there is no registered modifiers, then wrapper isn't needed.
    $wrapper_drawer_config = ['base_drawer_id' => $base_drawer_id, 'base_drawer_config' => $drawer_config];
    $wrapper_drawer_config['modifiers'] = $modifiers;

    // So we really will instanciate the wrapper drawer which has original base_drawer_id and drawer_config in its config.
    // The base drawer itself will be loaded seamlessly inside the wrapper drawer __construct(), added to the internal
    // reference property and used only internally (to it will be delegated method calls inside the wrapper).
    // @todo: these two lines are not needed
    $base_drawer_id = $wrapper_drawer_id;
    $drawer_config = $wrapper_drawer_config;

    return ['wrapper_drawer_id' => $wrapper_drawer_id, 'wrapper_drawer_config' => $wrapper_drawer_config];
  }

  /**
   * {@inheritdoc}
   */
  public function getDrawerPlugin() {

    // @todo: use getDrawerByPrefixedId()
    if (!isset($this->drawer_plugin)) {
      $common_drawer_id = $this->getDrawerId();
      if (!empty($common_drawer_id)) {
        $drawer_type = $this->getDrawerType();
        if ($drawer_type == VisualNStyleInterface::SUB_DRAWER_PREFIX) {
          $visualn_drawer_id = $common_drawer_id;

          $wrapper_plugin_components = static::getSubDrawerWrapperPluginArguments($visualn_drawer_id);
          $base_drawer_id = $wrapper_plugin_components['wrapper_drawer_id'];
          $drawer_config = $wrapper_plugin_components['wrapper_drawer_config'];
          $drawer_config['base_drawer_config'] = $this->getDrawerConfig() + $drawer_config['base_drawer_config'];
        }
        else {
          $base_drawer_id = $common_drawer_id;
          $drawer_config = [];
          $drawer_config = $this->getDrawerConfig() + $drawer_config;
        }
        // @todo: rename base_drawer_id variable to drawer_plugin_id to resemble that it can be a wrapper
        //    which can hardly be considered as a base drawer though technically is (a better choice would be to call
        //    such drawers just wrapper drawers but it should be mentioned somewhere in terminology dictionary)
        // @todo: load manager at object instantiation
        $this->drawer_plugin = \Drupal::service('plugin.manager.visualn.drawer')->createInstance($base_drawer_id, $drawer_config);

        //    Actually we are talking about subdrawing framework here. Also some modifiers
        //    could apply to some wrappable drawers and not apply to others.

        // @todo: Wrapper drawers have "role" key equal to "wrapper" in their annotation.
        //    By defualt drawers role is set to "drawer" so they can be used when creating styles or subdrawers.

        // @todo: Prepare a wrapper API description (i.e. the functions from the API, to which modifiers should be applied,
        //     must not be used by base drawers internally, inside other methods, because when used internally inside other
        //     methods their output/input can't be modified. E.g. buildConfigurationForm() must not be used inside any other
        //     drawer methods).
      }
    }

    return $this->drawer_plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function getDrawerType() {
    return $this->drawer_type;
  }

  /**
   * {@inheritdoc}
   */
  public function getDrawerConfig() {
    return $this->drawer_config;
  }

  /**
   * {@inheritdoc}
   */
  public function setDrawerConfig($drawer_config) {
    $this->drawer = $drawer_config;
    return $this;
  }

}
