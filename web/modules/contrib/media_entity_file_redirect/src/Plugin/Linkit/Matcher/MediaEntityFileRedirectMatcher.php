<?php

namespace Drupal\media_entity_file_redirect\Plugin\Linkit\Matcher;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\linkit\Plugin\Linkit\Matcher\EntityMatcher;

/**
 * Provides a LinkIt matcher for returning media entities.
 *
 * This matcher is identical to the normal entity matcher, except that
 * instead of returning the path as /media/{id}, it returns it as
 * /document/{id}.
 *
 * Even though we could have Substitution plugin which swaps out /media/{id}
 * with /document/{id}, that swap happens in a text filter, and editors would
 * still be exposed to the confusing /media/{id} path in the LinkIt dialog.
 *
 * This matcher will also work if you have canonical paths for media entities
 * disabled: https://www.drupal.org/project/drupal/issues/3017935
 *
 * @Matcher(
 *   id = "entity:media_entity_file_redirect",
 *   label = @Translation("Media: File Redirect"),
 *   target_entity = "media",
 * )
 */
class MediaEntityFileRedirectMatcher extends EntityMatcher {

  /**
   * {@inheritdoc}
   */
  protected function buildPath(EntityInterface $entity) {
    return Url::fromRoute('media_entity_file_redirect.file_redirect', ['media' => $entity->id()])->toString();
  }

}
