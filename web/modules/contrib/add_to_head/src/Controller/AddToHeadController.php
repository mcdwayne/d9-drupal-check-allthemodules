<?php
/**
 * @file
 * Contains \Drupal\add_to_head\Controller\DefaultController.
 */

namespace Drupal\add_to_head\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Core\Link;


/**
 * Default controller for the add_to_head module.
 */
class AddToHeadController extends ControllerBase {

  public function adminOverview() {
    // Get the defined profiles.
    $settings = add_to_head_get_settings();

    $add_profile_url = Url::fromRoute('add_to_head.add_profile');
    $link = Link::fromTextAndUrl($this->t('Add one now'), $add_profile_url)->toRenderable();

    $table = [
      '#type' => 'table',
      '#header' => [
        $this->t('Title'),
        $this->t('Paths'),
        $this->t('Roles'),
        $this->t('Scope'),
        $this->t('Operations'),
      ],
      '#empty' => t('No profiles configured yet. @link.', array(
        '@link' => \Drupal::service('renderer')->render($link),
      )),
    ];

    $role_names = user_role_names();
    // List each profile on the overview page.
    foreach ($settings as $delta => $profile) {
      $key = $profile['name'];

      $path_list = [
        '#theme' => 'item_list',
        '#title' => \Drupal\Component\Utility\Unicode::ucfirst($profile['paths']['visibility']),
      ];
      foreach (explode("\n", $profile['paths']['paths']) as $path) {
        $path_list['#items'][] = [ '#plain_text' => $path ];
      }

      $role_list = [
        '#theme' => 'item_list',
        '#title' => \Drupal\Component\Utility\Unicode::ucfirst($profile['roles']['visibility']),
        '#items' => array_intersect_key($role_names, array_flip($profile['roles']['list'])),
      ];

      $table[$key] = [
        'name' => [ '#plain_text' => $profile['name'] ],
        'paths' => $path_list,
        'roles' => $role_list,
        'scope' => [ '#plain_text' => \Drupal\Component\Utility\Unicode::ucfirst($profile['scope']) ],
      ];

      // Show all possible operations on the profile.
    // @FIXME - Check if profile is code or in settings.
//      if (in_array($settings, \Drupal::config('add_to_head.settings')->get('add_to_head_profiles'))) {
      if (TRUE) {
        // This profile is in the DB. It can be modified through the Web UI.
        $params = [
          'profile' => $profile['name']
        ];
        $table[$key]['ops'] = [
          '#type' => 'operations',
          '#links' => [
            'edit' => [
              'title' => $this->t('Edit'),
              'url' => Url::fromRoute('add_to_head.edit_profile', $params),
            ],
            'delete' => [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('add_to_head.delete_profile', $params),
            ],
          ]
        ];
      }
      else {
        // The profile is in code only. It cannot be edited from here so show a message.
        $table[$key]['ops']['#plain_text'] = $this->t('None (in code)');
      }
    }

    return $table;
  }
}
