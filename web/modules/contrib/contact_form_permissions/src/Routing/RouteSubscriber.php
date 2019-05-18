<?php

namespace Drupal\contact_form_permissions\Routing;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Alter contact form routes.
 *
 * @package Drupal\contact_form_permissions\Routing
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * RouteSubscriber constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {

    if ($route = $collection->get('contact.form_add')) {
      $permissions = $route->getRequirement('_permission');
      // @TODO: Is it safe to add our permission like this?
      $route->setRequirement('_permission', $permissions . ',add contact form');
    }

    if ($route = $collection->get('entity.contact_form.edit_form')) {
      $route->setRequirement(
        '_contact_form_permissions_edit_access',
        '{contact_form}'
      );
    }

    if ($route = $collection->get('entity.contact_form.delete_form')) {
      $route->setRequirement(
        '_contact_form_permissions_delete_access',
        '{contact_form}'
      );
    }

    // @TODO: Refactor this part.
    if ($this->moduleHandler->moduleExists('contact_storage')) {
      if ($route = $collection->get('contact_storage.settings')) {
        $permissions = $route->getRequirement('_permission');
        // @TODO: Is it safe to add our permission like this?
        $route->setRequirement(
          '_permission',
          $permissions . ',manage settings contact storage form'
        );
      }

      if ($route = $collection->get('entity.contact_form.disable')) {
        $route->setRequirement(
          '_contact_form_permissions_activation_access',
          '{contact_form}'
        );
      }

      if ($route = $collection->get('entity.contact_form.enable')) {
        $route->setRequirement(
          '_contact_form_permissions_activation_access',
          '{contact_form}'
        );
      }
    }
  }

}
