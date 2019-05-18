<?php

namespace Drupal\api_tokens_example\Plugin\ApiToken;

use Drupal\Core\Link;
use Drupal\user\Entity\User;
use Drupal\api_tokens\ApiTokenBase;

/**
 * Provides a User Link API token.
 *
 * Token examples:
 * - [api:user-link/]
 * - [api:user-link[123]/]
 *
 * @ApiToken(
 *   id = "user-link",
 *   label = @Translation("User link"),
 *   description = @Translation("Renders a user link.")
 * )
 */
class UserLinkApiToken extends ApiTokenBase {

  /**
   * {@inheritdoc}
   */
  public function validate(array $params) {
    // For [api:user-link/] token:
    //$params = [
    //  'id' => NULL,
    //];

    // For [api:user-link[123]/] token:
    //$params = [
    //  'id' => 123,
    //];

    // If "id" is provided, check that it is a valid user ID.
    if (isset($params['id']) && !preg_match('@\d+@', $params['id'])) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Build callback.
   *
   * @param int $id
   *   (optional) The user ID.
   *
   * return array
   *   A renderable array.
   *
   * @see \Drupal\api_tokens\ApiTokenPluginInterface::build();
   */
  public function build($id = NULL) {
    $build = [];
    if ($id) {
      $user = User::load($id);
      if ($user && $user->access('view')) {
        $build = ['#markup' => $user->link()];
        $this->addCacheContexts(['user']);
        $this->addCacheableDependency($user);
      }
    }
    else {
      $build = Link::createFromRoute(\Drupal::currentUser()->getDisplayName(), 'user.page')->toRenderable();
      $this->addCacheContexts(['user']);
    }

    return $build;
  }

}
