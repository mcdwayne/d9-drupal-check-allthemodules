<?php
namespace Drupal\splashify\Service;

use Drupal\Component\Utility\Unicode;
use Drupal\splashify\Entity\SplashifyEntity;
use Drupal\splashify\Entity\SplashifyGroupEntity;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class SplashifyInjection.
 *
 * @package Drupal\splashify\Service
 */
class SplashifyInjection {

  protected $splash = NULL;

  /**
   * Default time line.
   */
  protected $timeLine = [
    // Set to expire in one year.
    'once' => 31536000,
    // Set to expire in 24 hours.
    'daily' => 86400,
    // Set to expire in 7 days.
    'weekly' => 604800,
    // Always when load page.
    'always' => 0,
  ];

  /**
   * Check is splash exist. If exist then remembered.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The incoming request object.
   *
   * @return bool
   *   The check result.
   */
  public function isSplashExist(Request $request = NULL) {
    // At the first check in the request no session. It should pass this check.
    if (is_object($request) && !$request->hasSession()) {
      return TRUE;
    }

    if (!empty($this->splash)) {
      return TRUE;
    }

    $this->splash = $this->findSplash();

    return !empty($this->splash);
  }

  /**
   * Received all of splash groups that can be displayed on the current page.
   *
   * @return array
   *   Array of SplashifyGroupsEntity.
   */
  private function getPageGroups() {
    $groups = SplashifyGroupEntity::loadMultiple();

    $page_groups = [];

    foreach ($groups as $group) {
      if ($this->checkRole($group) && $this->checkWhere($group)) {
        $page_groups[$group->id()] = $group;
      }
    }

    return $page_groups;
  }

  /**
   * Received all of splashes that can be displayed on the current page.
   *
   * @return array
   *   Array of SplashifyEntity.
   */
  private function getSplashes() {
    $page_groups = $this->getPageGroups();
    $page_groups_id = array_keys($page_groups);

    if (empty($page_groups_id)) {
      return [];
    }

    $splashes_id = \Drupal::entityQuery('splashify_entity')
      ->condition('status', 1)
      ->sort('field_weight', 'DESC')
      ->sort('id', 'DESC')
      ->condition('field_group', $page_groups_id, 'IN')
      ->execute();

    if (empty($splashes_id)) {
      return [];
    }

    $splashes = SplashifyEntity::loadMultiple($splashes_id);

    return $splashes;
  }

  /**
   * Choosing an entity which will be displayed.
   *
   * @return SplashifyEntity|null
   *   Splash that passes all conditions and has the biggest weight.
   */
  private function findSplash() {
    $splashes = $this->getSplashes();

    // Check which item need display.
    foreach ($splashes as $splash) {
      if ($this->checkFrequency($splash)) {
        return $splash;
      }
    }

    return NULL;
  }

  /**
   * Generated render element.
   *
   * @param $splash
   *   Splash which will be displayed.
   * @return array
   */
  private function getRenderElement($splash) {
    $build = [];
    $mode = $splash->getGroup()->getMode();

    switch ($mode) {
      case 'redirect':
        $build = [
          '#attached' => [
            'drupalSettings' => [
              'splashify' => [
                'mode' => 'redirect',
                'url' => '/splashify/' . $splash->id(),
              ],
            ],
            'library' => [
              'splashify/redirect',
            ],
          ],
        ];

        break;

      case 'window':
        $size = explode('x', $splash->getGroup()->getSize());
        $width = is_numeric($size[0]) ? $size[0] : 800;
        $height = is_numeric($size[1]) ? $size[1] : 600;

        $build = [
          '#attached' => [
            'drupalSettings' => [
              'splashify' => [
                'mode' => 'window',
                'url' => '/splashify/' . $splash->id(),
                'size' => "width={$width}, height={$height}",
              ],
            ],
            'library' => [
              'splashify/window',
            ],
          ],
        ];

        break;

      case 'full_screen':
        $build = [
          '#theme' => 'splashify',
          '#splashify_content' => $splash->getContent(),
          '#attached' => [
            'drupalSettings' => [
              'splashify' => [
                'mode' => 'full_screen',
              ],
            ],
            'library' => [
              'splashify/full_screen',
            ],
          ],
        ];
        break;

      case 'lightbox':
        $size = explode('x', $splash->getGroup()->getSize());
        \Drupal::service('colorbox.attachment')->attach($build);

        $build['#attached']['drupalSettings']['splashify'] = [
          'mode' => 'lightbox',
          'url' => '/splashify/' . $splash->id(),
          'width' => is_numeric($size[0]) ? $size[0] : 800,
          'height' => is_numeric($size[1]) ? $size[1] : 600,
        ];

        $build['#attached']['library'][] = 'splashify/lightbox';
        break;
    }

    $build['#cache']['max-age'] = 0;
    $build['#attached']['drupalSettings']['splashify']['refferer_check'] = !$splash->getGroup()
      ->isDisableReferrerCheck();
    array_unshift($build['#attached']['library'], 'splashify/splash_init');

    return $build;
  }

  /**
   * Returns render element.
   *
   * If this method is called it is considered that splash was shown.
   *
   * @return array
   */
  public function getAttach() {

    if (!$this->isSplashExist()) {
      return [];
    }

    setcookie("splashify[" . $this->splash->id() . "]", REQUEST_TIME, NULL, '/');
    return $this->getRenderElement($this->splash);
  }

  /**
   * Check if the role of the current user pass the conditions in the group.
   *
   * @param $group
   * @return bool
   */
  private function checkRole($group) {
    // Get user account.
    $account = \Drupal::currentUser()->getAccount();

    // Check whether use role setting is checked.
    if ($group->isRestrictRoles()) {

      $account_roles = $account->getRoles();
      $group_roles = $group->getRoles();

      return !empty(array_intersect($account_roles, $group_roles));
    }

    return TRUE;
  }

  /**
   * Check if the current page pass the conditions in the group.
   *
   * @param $group
   * @return bool
   */
  private function checkWhere($group) {
    $where = $group->getWhere();

    switch ($where) {
      case 'all':
        return TRUE;

      case 'home':
        $is_front = \Drupal::service('path.matcher')->isFrontPage();
        return $group->isOpposite() ? !$is_front : $is_front;

      case 'list':
        $pages = Unicode::strtolower($group->getListPages());

        $path = \Drupal::service('path.current')->getPath();
        // Do not trim a trailing slash if that is the complete path.
        $path = $path === '/' ? $path : rtrim($path, '/');
        $path_alias = Unicode::strtolower(\Drupal::service('path.alias_manager')
          ->getAliasByPath($path));
        $path_matcher = \Drupal::service('path.matcher');

        $is_match = $path_matcher->matchPath($path_alias, $pages) ||
          (($path != $path_alias) && $path_matcher->matchPath($path, $pages));

        return $group->isOpposite() ? !$is_match : $is_match;
    }

    return TRUE;
  }

  /**
   * Checks can we display splash again from the group.
   *
   * @param $splash
   * @return bool
   */
  private function checkFrequency($splash) {
    $frequency = $splash->getGroup()->getOften();

    if ($frequency == 'never') {
      return FALSE;
    }

    $cookie = \Drupal::request()->cookies;
    $splashify_cookies = $cookie->get('splashify');

    if (!is_array($splashify_cookies) || !array_key_exists($splash->id(), $splashify_cookies)) {
      return TRUE;
    }

    $expired_time = REQUEST_TIME - $splashify_cookies[$splash->id()];
    return $expired_time >= $this->timeLine[$frequency];
  }

}
