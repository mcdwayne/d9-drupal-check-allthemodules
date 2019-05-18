<?php

namespace Drupal\message_thread\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\views\Views;
use Drupal\Core\Routing\RouteProviderInterface;

/**
 * Defines dynamic local tasks.
 */
class DynamicLocalTasks extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The template storage manager.
   *
   * @var Drupal\Core\Entity\EntityStorageInterface
   */
  protected $templateStorage;

  /**
   * The entity type manager.
   *
   * @var Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The route provider.
   *
   * @var Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * Constructs the message thread template  form.
   */
  public function __construct($base_plugin_id, EntityStorageInterface $template_storage, EntityTypeManager $entity_type_manager, RouteProviderInterface $route_provider) {
    $this->templateStorage = $template_storage;
    $this->entityTypeManager = $entity_type_manager;
    $this->routeProvider = $route_provider;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $base_plugin_id,
      $container->get('entity_type.manager')->getStorage('message_template'),
      $container->get('entity_type.manager'),
      $container->get('router.route_provider')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {

    // Create tabs for each message thread type.
    $thread_templates = $this->entityTypeManager->getListBuilder('message_thread_template')->load();
    foreach ($thread_templates as $name => $template) {
      $settings = $template->getSettings();

      // Thread page tabs.
      $view_route = 'view.' . $settings['thread_view_id'] . '.' . $settings['thread_view_display_id'];
      $exists = count($this->routeProvider->getRoutesByNames([$view_route])) === 1;
      if (!$exists) {
        continue;
      }
      // User page tab.
      $view = Views::getView($settings['thread_view_id']);
      $view->setDisplay($settings['thread_view_display_id']);
      if (!$view->hasUrl()) {
        continue;
      }
      $this->derivatives['message_thread.' . $name . '.user'] = $base_plugin_definition;
      $this->derivatives['message_thread.' . $name . '.user']['title'] = $template->label();
      $this->derivatives['message_thread.' . $name . '.user']['route_name'] = 'message_thread.' . $name;
      $this->derivatives['message_thread.' . $name . '.user']['base_route'] = 'entity.user.canonical';
      $this->derivatives['message_thread.' . $name . '.user']['weight'] = 100;

    }

    return $this->derivatives;
  }

}
