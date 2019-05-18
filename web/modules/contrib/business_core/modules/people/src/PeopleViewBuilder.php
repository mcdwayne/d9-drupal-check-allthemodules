<?php

namespace Drupal\people;

use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Link;

/**
 * View builder handler for peoples.
 */
class PeopleViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildComponents(array &$build, array $entities, array $displays, $view_mode) {
    /** @var \Drupal\people\PeopleInterface[] $entities */
    if (empty($entities)) {
      return;
    }

    parent::buildComponents($build, $entities, $displays, $view_mode);

    foreach ($entities as $id => $entity) {
      $bundle = $entity->bundle();
      $display = $displays[$bundle];

      if ($display->getComponent('user_roles')) {
        $build[$id]['user_roles'] = [
          '#lazy_builder' => [
            get_called_class() . '::renderUserRoles',
            [$entity->id(), $view_mode],
          ],
        ];
      }
    }
  }

  /**
   * #lazy_builder callback; builds a people's user roles.
   *
   * @param int $people_id
   *   The people entity ID.
   * @param string $view_mode
   *   The view mode in which the people entity is being viewed.
   *
   * @return array
   *   A renderable array representing the people user roles.
   */
  public static function renderUserRoles($people_id, $view_mode) {
    $items = [];
    $userStorage = \Drupal::entityTypeManager()->getStorage('user');
    if ($users = $userStorage->loadByProperties(['people' => $people_id])) {
      foreach ($users as $user) {
        $items[$user->id()] = [
          '#markup' => $user->toLink()->toString(),
        ];

        $roles = [];
        foreach ($user->get('roles') as $role) {
          $entity = $role->entity;
          $roles[$entity->id()] = $entity->label();
        }
        $items[$user->id()]['roles'] = [
          '#theme' => 'item_list',
          '#items' => $roles,
        ];
      }
    }
    $add_link = Link::createFromRoute(
      t('Add account'),
      'people.people_user.add_form',
      ['people' => $people_id]
    );
    $build = [
      '#title' => t('User roles'),
      '#theme' => 'item_list',
      '#items' => $items,
      '#empty' => $add_link,
    ];

    return $build;
  }

}
