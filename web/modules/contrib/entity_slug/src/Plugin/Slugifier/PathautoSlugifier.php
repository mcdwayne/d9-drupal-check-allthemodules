<?php

namespace Drupal\entity_slug\Plugin\Slugifier;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\entity_slug\Annotation\Slugifier;
use Drupal\pathauto\AliasCleaner;

/**
 * @Slugifier(
 *   id = "pathauto",
 *   name = @Translation("Pathauto cleaner"),
 *   weight = 50,
 * )
 */
class PathautoSlugifier extends SlugifierBase {

  /**
   * {@inheritdoc}
   */
  public function slugify($input, FieldableEntityInterface $entity) {
    /** @var AliasCleaner $aliasCleaner */
    $aliasCleaner = \Drupal::service('pathauto.alias_cleaner');

    $slug = $aliasCleaner->cleanString($input);

    return $slug;
  }

  public function information() {
    $information = [];

    $information[] = $this->t('The Pathauto slugifier will automatically convert the slug value into a URL-friendly string by removing or encoding characters.');
    $information[] = $this->t('The default settings for Pathauto are used, so you may change them and then re-save the slug for the change to take effect.');

    return array_merge($information, parent::information());
  }
}
