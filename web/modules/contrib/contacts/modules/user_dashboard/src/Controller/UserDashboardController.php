<?php

namespace Drupal\contacts_user_dashboard\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller routines for user routes.
 */
class UserDashboardController extends ControllerBase {

  /**
   * Constructs a UserDashboardController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Extension\ModuleHandler $module_handler
   *   The module handler.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user service.
   */
  public function __construct(EntityTypeManager $entity_type_manager, ModuleHandler $module_handler, AccountInterface $current_user) {
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('module_handler'),
      $container->get('current_user')
    );
  }

  /**
   * Redirects users to their user dashboard page.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user context.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Returns a redirect to the summary tab of the currently logged in user.
   */
  public function userPage(UserInterface $user = NULL) {
    $route_params = [
      'user' => $user ? $user->id() : $this->currentUser->id(),
    ];

    return $this->redirect('contacts_user_dashboard.summary', $route_params);
  }

  /**
   * Summary page for user dashboard.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user context.
   *
   * @return array
   *   Render array for
   */
  public function userSummaryPage(UserInterface $user) {
    // @todo Find better way to add row class.
    $content = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['row'],
      ],
    ];

    $user_view_builder = $this->entityTypeManager->getViewBuilder('user');
    $content['user'] = [
      '#type' => 'user_dashboard_summary',
      '#buttons' => [
        [
          'text' => $this->t('Update details'),
          'route_name' => 'entity.profile.type.user_profile_form',
          'route_parameters' => [
            'user' => $user->id(),
            'profile_type' => 'crm_indiv',
          ],
        ],
        [
          'text' => $this->t('Change password'),
          'route_name' => 'entity.user.edit_form',
          'route_parameters' => ['user' => $user->id()],
        ],
      ],
      '#title' => 'Your details',
      '#content' => $user_view_builder->view($user, 'user_dashboard'),
    ];

    // Alter hook to add to the summary blocks.
    $this->moduleHandler->alter('contacts_user_dashboard_user_summary_blocks', $content, $user);

    return $content;
  }

}
