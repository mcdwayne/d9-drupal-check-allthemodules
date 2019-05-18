<?php

namespace Drupal\mcapi_forms\EventSubscriber;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;

/**
 * Subscriber to create a router item for each transaction form display.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      RoutingEvents::ALTER => [['onAlterRoutes', 0]]
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Add a route for each form.
    foreach (mcapi_form_displays_load() as $mode => $display) {
      if ($path = $display->getThirdPartySetting('mcapi_forms', 'path')) {
        $route = new Route($path);
        $route->setDefaults([
          '_entity_form' => 'mcapi_transaction.' . $mode,
          '_title_callback' => '\Drupal\mcapi_forms\FirstPartyTransactionForm::title',
        ]);
        $route->setRequirement('_entity_create_access', 'mcapi_transaction');
        if ($perm = $display->getThirdPartySetting('mcapi_forms', 'permission')) {
          $route->setRequirement('_permission', $perm);
        }
        $route->setOptions([
          'parameters' => [
            'mode' => $mode,
          ],
        ]);
        $collection->add('mcapi.1stparty.' . $mode, $route);
      }
    }

    // Add the translation overview page for the EntityDisplayForms
    if (\Drupal::moduleHandler()->moduleExists('config_translation')) {
      $route = new Route('/admin/accounting/transactions/form-display/manage/{entity_form_display}');
      $route->setDefaults([
        '_controller' => '\Drupal\config_translation\Controller\ConfigTranslationController::itemPage',
        '_title' => 'Translate transaction forms',
        'plugin_id' => 'entity_form_display'
      ]);
      $route->setRequirement('_permission', 'configure mcapi');
      $route->setOptions([
        '_admin_route' => TRUE,
        'compiler_class' => '\Drupal\Core\Routing\RouteCompiler',
        'parameters'  => [
          'entity_form_display'=> [
            'type' => 'entity:entity_form_display',
            'converter' => 'drupal.proxy_original_service.paramconverter.configentity_admin'
          ]
        ]
      ]);
      $collection->add('entity.entity_form_display.mcapi_form_overview_dummy', $route);
    }
  }

}
