<?php

namespace Drupal\media_entity_file_redirect\Plugin\Linkit\Substitution;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Url;
use Drupal\linkit\SubstitutionInterface;

/**
 * A substitution plugin for the URL to a file associated with a media entity.
 *
 * @Substitution(
 *   id = "media_file_redirect",
 *   label = @Translation("URL that redirects to direct file path"),
 * )
 */
class MediaFileRedirect extends PluginBase implements SubstitutionInterface {

  /**
   * {@inheritdoc}
   */
  public function getUrl(EntityInterface $entity) {
    return Url::fromRoute('media_entity_file_redirect.file_redirect', ['media' => $entity->id()])->toString(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(EntityTypeInterface $entity_type) {
    return $entity_type->entityClassImplements('Drupal\media\MediaInterface');
  }

}
