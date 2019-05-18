<?php

namespace Drupal\entity_slug\Plugin\Slugifier;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\entity_slug\Annotation\Slugifier;

/**
 * @Slugifier(
 *   id = "token",
 *   name = @Translation("Token replacer"),
 *   weight = -50,
 * )
 */
class TokenSlugifier extends SlugifierBase {

  /**
   * {@inheritdoc}
   */
  public function slugify($input, FieldableEntityInterface $entity) {
    $slug = \Drupal::token()->replace($input, [
      $entity->getEntityTypeId() => $entity,
    ]);

    // Remove invalid tokens
    $slug = preg_replace('/\[[^\]]+\]/', '', $slug);

    return $slug;
  }

  /**
   * {@inheritdoc}
   */
  public function information() {
    $information = [];

    $information[] = $this->t('The Token slugifier will replace all valid tokens with their respective real values.');

    return array_merge($information, parent::information());
  }
}
