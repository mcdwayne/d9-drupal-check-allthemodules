<?php

/**
 * @file
 * Contains \Drupal\user_badges\Controller\BadgeController.
 */

namespace Drupal\user_badges\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\user_badges\BadgeInterface;
use Drupal\user_badges\BadgeTypeInterface;
use Drupal\Core\Url;

/**
 * Class BadgeController.
 *
 * @package Drupal\user_badges\Controller
 */
class BadgeController extends ControllerBase {
  /**
   * Addpage.
   *
   * @return string
   *   Return links for badge creation.
   */
  public function addPage() {
    $build = [
      '#theme' => 'item_list'
    ];

    $content = [];
    $types = [];

    // Only use badge types the user has access to.
    foreach ($this->entityManager()->getStorage('badge_type')->loadMultiple() as $type) {
      $access = $this->entityManager()->getAccessControlHandler('badge')->createAccess($type->id(), NULL, [], TRUE);
      if ($access->isAllowed()) {
        $content[] = \Drupal::l($type->label(), new Url('user_badges.badge_controller_add', ['badge_type' => $type->id()]));
        $types[] = $type;
      }
    }

    // Bypass the /admin/structure/badge/add listing if only one content type is available.
    if (count($types) == 1) {
      $type = array_shift($types);
      return $this->redirect('user_badges.badge_controller_add', ['badge_type' => $type->id()]);
    }

    $build['#items'] = $content;

    return $build;
  }
  /**
   * Add.
   *
   * @return form
   *   Returns badge creation form.
   */
  public function add(BadgeTypeInterface $badge_type) {
    $badge = $this->entityManager()->getStorage('badge')->create([
      'type' => $badge_type->id(),
    ]);

    $form = $this->entityFormBuilder()->getForm($badge);
    return $form;
  }

  /**
   * The _title_callback for the user_badges.badge_controller_add route.
   *
   * @param \Drupal\user_badges\BadgeTypeInterface $badge_type
   *   The current node.
   *
   * @return string
   *   The page title.
   */
  public function addBadgeTitle(BadgeTypeInterface $badge_type) {
    return $this->t('Create @name', ['@name' => $badge_type->label()]);
  }

}
