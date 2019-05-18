<?php

namespace Drupal\people\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\people\PeopleInterface;

/**
 * Returns responses for People routes.
 */
class PeopleController extends ControllerBase {

  /**
   * Provides the user submission form.
   *
   * @param \Drupal\people\PeopleInterface $people
   *   The people entity for the user.
   *
   * @return array
   *   A user submission form.
   */
  public function peopleUserAddForm(PeopleInterface $people) {
    $user = $this->entityTypeManager()->getStorage('user')->create([
      'people' => $people->id(),
    ]);

    $form = $this->entityFormBuilder()->getForm($user, 'register');

    return $form;
  }

}
