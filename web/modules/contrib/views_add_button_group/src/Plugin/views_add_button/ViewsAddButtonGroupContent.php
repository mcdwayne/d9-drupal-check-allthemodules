<?php

namespace Drupal\views_add_button_group\Plugin\views_add_button;

use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Url;
use Drupal\views_add_button\ViewsAddButtonInterface;

/**
 * Integrates Views Add Button with group_content entities.
 *
 * @ViewsAddButton(
 *   id = "views_add_button_group_content",
 *   label = @Translation("ViewsAddButtonGroupContent"),
 *   target_entity = "group_content"
 * )
 */
class ViewsAddButtonGroupContent extends PluginBase implements ViewsAddButtonInterface {

  /**
   * Plugin Description.
   *
   * @return string
   *   A string description.
   */
  public function description() {
    return $this->t('Views Add Button URL Generator for Group Content entities');
  }

  /**
   * Get the machine name of the entity bundle from a hashed string.
   *
   * @param string $bundle_string
   *   The hashed group_content bundle string to parse.
   *
   * @return string
   *   The bundle machine name.
   */
  public static function getBundle($bundle_string) {
    $storage_config = \Drupal::configFactory()->getEditable('group.content_type.' . $bundle_string);
    $plugin_id = $storage_config->getOriginal('content_plugin');
    return $plugin_id;
  }

  /**
   * Check for access to the appropriate "add" route.
   *
   * @param string $entity_type
   *   Entity id as a machine name.
   * @param string $bundle
   *   The bundle string.
   * @param string $context
   *   Entity context string, a comma-separated list of values.
   *
   * @return bool
   *   Whether we have access.
   */
  public static function checkAccess($entity_type, $bundle, $context) {
    $route = '';
    $c = explode(',', $context);
    if (!isset($c[0]) || !is_numeric($c[0])) {
      return FALSE;
    }
    /*
     * We are expecting a bundle of the type
     * group-group_[entity_type]-[entity_type_bundle].
     */
    $b = explode('-', $bundle);
    $plugin_id = '';
    /*
     * For entities with shorter names, it will be of the type
     * group_type-group_entity-group_entity_bundle.
     */
    if (count($b) === 3) {
      $plugin_id = implode(':', [$b[1], $b[2]]);
    }
    // Memberships are usually of the type [group_type]-group_membership.
    elseif (count($b) === 2 && $b[1] === 'group_membership') {
      $plugin_id = 'group_membership';
      /*
       * In the case of group membership, we may add a second context parameter,
       * 'join' , to make a join link.
       *
       * Alternatively, We can use 'leave' to generate a leave link
       * If it is blank, or anything else, we get the default
       * "add a member" form.
       */
      if (isset($c[1])) {
        switch (trim($c[1])) {
          case 'join':
            $route = 'entity.group.join';
            break;

          case 'leave':
            $route = 'entity.group.leave';
            break;

        }
      }
      else {
        $route = 'entity.group_content.add_form';
      }
    }
    /*
     * For entities with a long name, i.e. group_content_type_12d187f0f3346 ,
     * extract the plugin_id.
     */
    elseif (count($b) === 1) {
      $plugin_id = ViewsAddButtonGroupContent::getBundle($b[0]);
    }
    // Create URL from the data above.
    if (empty($route) && isset($c[0]) && !empty($c[0]) && $plugin_id) {
      /* If we pass "add" with the group context, we can generate the
       * "relate existing entity to group" link, instead of the create link.
       */
      if (isset($c[1]) && trim($c[1]) === 'add') {
        $route = 'entity.group_content.add_form';
      }
      $route = 'entity.group_content.create_form';
    }
    // At this point we should have a route. If not, deny access.
    if (!empty($route) && !empty($plugin_id)) {
      $accessManager = \Drupal::service('access_manager');
      return $accessManager->checkNamedRoute($route, ['group' => $c[0], 'plugin_id' => $plugin_id], \Drupal::currentUser());
    }
    else {
      return FALSE;
    }
  }

  /**
   * Generate the Add Button Url.
   *
   * @param string $entity_type
   *   Entity id as a machine name.
   * @param string $bundle
   *   The bundle string.
   * @param array $options
   *   Array of options to be used when building the Url, and Link.
   * @param string $context
   *   Entity context string, a comma-separated list of values.
   *
   * @return \Drupal\Core\Url
   *   The Url to use in the Add Button link.
   */
  public static function generateUrl($entity_type, $bundle, array $options, $context = '') {
    $c = explode(',', $context);
    /*
     * We are expecting a bundle of the type
     * group-group_[entity_type]-[entity_type_bundle]
     */
    $b = explode('-', $bundle);
    $plugin_id = '';
    /*
     * For entities with shorter names, it will be of the type
     * group_type-group_entity-group_entity_bundle
     */
    if (count($b) === 3) {
      $plugin_id = implode(':', [$b[1], $b[2]]);
    }
    // Memberships are usually of the type [group_type]-group_membership.
    elseif (count($b) === 2 && $b[1] === 'group_membership') {
      $plugin_id = 'group_membership';
      /*
       * In the case of group membership, we may add a second context
       * parameter, 'join' , to make a join link.
       *
       * Alternatively, We can use 'leave' to generate a leave link
       * If it is blank, or anything else, we get the default
       * "add a member" form.
       */
      if (isset($c[1])) {
        switch (trim($c[1])) {
          case 'join':
            $url = Url::fromRoute('entity.group.join', ['group' => $c[0]], $options);
            return $url;

          case 'leave':
            $url = Url::fromRoute('entity.group.leave', ['group' => $c[0]], $options);
            return $url;

        }
      }
      $url = Url::fromRoute('entity.group_content.add_form', ['group' => $c[0], 'plugin_id' => $plugin_id], $options);
      return $url;
    }
    /*
     * For entities with a long name, i.e. group_content_type_12d187f0f3346,
     * extract the plugin_id.
     */
    elseif (count($b) === 1) {
      $plugin_id = ViewsAddButtonGroupContent::getBundle($b[0]);
    }
    // Create URL from the data above.
    if (isset($c[0]) && !empty($c[0]) && $plugin_id) {
      /*
       * If we pass "add" with the group context, we can generate the "relate
       * existing entity to group" link, instead of the create link.
       */
      if (isset($c[1]) && trim($c[1]) === 'add') {
        $url = Url::fromRoute('entity.group_content.add_form', ['group' => $c[0], 'plugin_id' => $plugin_id], $options);
        return $url;
      }
      $url = Url::fromRoute('entity.group_content.create_form', ['group' => $c[0], 'plugin_id' => $plugin_id], $options);
      return $url;
    }
  }

}
