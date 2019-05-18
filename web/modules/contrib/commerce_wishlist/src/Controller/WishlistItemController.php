<?php

namespace Drupal\commerce_wishlist\Controller;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the wishlist item pages.
 */
class WishlistItemController implements ContainerInjectionInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new WishlistController object.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   */
  public function __construct(AccountInterface $current_user, EntityTypeManagerInterface $entity_type_manager, FormBuilderInterface $form_builder, RouteMatchInterface $route_match) {
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->formBuilder = $form_builder;
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('form_builder'),
      $container->get('current_route_match')
    );
  }

  /**
   * Builds the item details form.
   *
   * @return array
   *   The rendered form.
   */
  public function detailsForm() {
    $wishlist_item = $this->routeMatch->getParameter('commerce_wishlist_item');
    $form_object = $this->entityTypeManager->getFormObject('commerce_wishlist_item', 'details');
    $form_object->setEntity($wishlist_item);
    $form_state = new FormState();

    return $this->formBuilder->buildForm($form_object, $form_state);
  }

}
