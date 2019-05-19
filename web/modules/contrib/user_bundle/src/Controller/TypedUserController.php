<?php

namespace Drupal\user_bundle\Controller;

use Drupal\user\Controller\UserController;
use Drupal\user_bundle\UserTypeInterface;

/**
 * Controller routines for typed user routes.
 */
class TypedUserController extends UserController {

  /**
   * Displays add user links for available user types.
   *
   * Redirects to admin/people/create/[type] if only one user type is available.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   A render array for a list of the user types that can be added; however,
   *   if there is only one user type defined for the site, the function
   *   will return a RedirectResponse to the user add page for that one user
   *   type.
   */
  public function adminCreatePage() {
    $build = [
      '#theme' => 'user_bundle_user_add_list',
      '#cache' => [
        'tags' => $this->entityManager()->getDefinition('user_type')->getListCacheTags(),
      ],
    ];

    $content = $this->entityManager()->getStorage('user_type')->loadMultiple();

    // Bypass the types listing if only one user type is available.
    if (count($content) == 1) {
      $type = array_shift($content);
      return $this->redirect('user.admin_create_form', ['user_type' => $type->id()]);
    }

    $build['#content'] = $content;

    return $build;
  }

  /**
   * Provides the admin user creation form.
   *
   * @param \Drupal\user_bundle\UserTypeInterface $user_type
   *   The user type entity for the user.
   *
   * @return array
   *   A user creation form.
   */
  public function adminCreateForm(UserTypeInterface $user_type) {
    $user = $this->entityManager()->getStorage('user')->create([
      'type' => $user_type->id(),
    ]);

    $form = $this->entityFormBuilder()->getForm($user, 'register');

    return $form;
  }

  /**
   * The _title_callback for the user.admin_create_form route.
   *
   * @param \Drupal\user_bundle\UserTypeInterface $user_type
   *   The user type entity for the user.
   *
   * @return string
   *   The page title.
   */
  public function adminCreateFormPageTitle(UserTypeInterface $user_type) {
    return $this->t('Add @name', ['@name' => $user_type->label()]);
  }

}
