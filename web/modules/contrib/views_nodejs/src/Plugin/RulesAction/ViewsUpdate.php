<?php

/**
 * @file
 * Contains Drupal\views_nodejs\Plugin\RulesAction\ViewsUpdate.
 */

namespace Drupal\views_nodejs\Plugin\RulesAction;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\rules\Core\RulesActionBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides "Views Update" rules action.
 *
 * @RulesAction(
 *   id = "views_nodejs",
 *   label = @Translation("Views Update"),
 *   category = @Translation("node.js"),
 *   context = {
 *     "subject" = @ContextDefinition("string",
 *       label = @Translation("Views"),
 *       description = @Translation("One can select views which need dynamical update."),
 *     ),
 *   }
 * )
 */
class ViewsUpdate extends RulesActionBase implements ContainerFactoryPluginInterface {


  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a SendEmail object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param Drupal\Core\Extension\ModuleHandlerInterface; $module_handler
   *   The module manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ModuleHandlerInterface $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler')
    );
  }

  /**
   * Update view with node.js.
   *
   * @param string $views
   *   List of views which should be updated.
   */
  protected function doExecute($views) {
    $views = unserialize($views);
    foreach ($views as $view) {
      $view_update = (object) array(
        'channel' => 'views_nodejs_' . $view['name'] . $view['display_id'],
        'callback' => 'viewsNodejs',
        'view_id' => $view['name'],
        'display_id' => $view['display_id'],
      );

      // One can change some settings (or add new) which are
      // going to node.js channel.
      $this->moduleHandler->alter('views_nodejs_channel', $view_update);
      nodejs_send_content_channel_message($view_update);
    }
  }

}
