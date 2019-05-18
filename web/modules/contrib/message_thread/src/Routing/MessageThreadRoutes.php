<?php

namespace Drupal\message_thread\Routing;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\views\Views;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines dynamic routes.
 */
class MessageThreadRoutes implements ContainerInjectionInterface {

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
  public function __construct(EntityTypeManager $entity_type_manager, $template_storage, RouteProviderInterface $route_provider) {
    $this->templateStorage = $template_storage;
    $this->entityTypeManager = $entity_type_manager;
    $this->routeProvider = $route_provider;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_type.manager')->getStorage('message_template'),
      $container->get('router.route_provider')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function routes() {

    $route_collection = new RouteCollection();
    // Create a route for each template.
    $thread_templates = $this->entityTypeManager->getListBuilder('message_thread_template')->load();

    foreach ($thread_templates as $name => $template) {

      $settings = $template->getSettings();

      // This is being called before the view route is being registered
      // when the module is first installed.
      // @todo is there are better way of handling this?
      $view_route = 'view.' . $settings['thread_view_id'] . '.' . $settings['thread_view_display_id'];
      $exists = count($this->routeProvider->getRoutesByNames([$view_route])) === 1;
      if (!$exists) {
        continue;
      }

      $view = Views::getView($settings['thread_view_id']);
      $view->setDisplay($settings['thread_view_display_id']);
      $url = $view->getUrl()->toString();

      // This is not going to work if the View is not placed in the User page.
      $url = str_replace('%2A', '{user}', $url);

      $route = new Route(
        $url,
        [
          '_controller' => '\Drupal\message_thread\Controller\MessageThreadController::inBox',
          '_title' => $template->label(),
        ],
        [
          '_permission' => 'create and receive ' . $template->id() . ' message threads',
        ]
      );
      $route_collection->add('message_thread.' . $template->id(), $route);
    }

    $route = (new Route('/message/thread/{message_thread}'))
      ->setDefaults([
        '_entity_view' => 'message_thread.full',
        '_title_callback' => 'Drupal\message_thread\Controller\MessageThreadController::messageThreadTitle',
      ])
      ->setRequirement('message_thread', '\d+')
      ->setRequirement('_entity_access', 'message_thread.view');

    $route_collection->add('entity.message_thread.canonical', $route);

    return $route_collection;

  }

}
