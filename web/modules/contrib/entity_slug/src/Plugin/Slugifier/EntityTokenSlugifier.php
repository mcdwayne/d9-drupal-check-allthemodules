<?php

namespace Drupal\entity_slug\Plugin\Slugifier;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\entity_slug\Annotation\Slugifier;
use Drupal\taxonomy\TermInterface;

/**
 * @Slugifier(
 *   id = "entity_token",
 *   name = @Translation("Entity token replacer"),
 *   weight = -60,
 * )
 */
class EntityTokenSlugifier extends SlugifierBase {

  /**
   * {@inheritdoc}
   */
  public function slugify($input, FieldableEntityInterface $entity) {
    $output = $input;

    while (preg_match('/\[entity_token:([^:\]]+):([^:\]]+):([^\]]+)\]/', $output, $matches)) {
      list($match, $entity_type, $entity_id, $entity_field) = $matches;

      $replacement = '';

      /** @var FieldableEntityInterface $entity */
      $entity = \Drupal::entityTypeManager()->getStorage($entity_type)->load($entity_id);

      if ($entity && $entity->hasField($entity_field) && !$entity->get($entity_field)->isEmpty()) {
        $replacement = \Drupal::token()->replace("[$entity_type:$entity_field]", [$entity_type => $entity]);
      }

      $output = str_replace($match, $replacement, $input);
    }

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function information() {
    $information = [];

    $information[] = $this->t('The Term Parent slugifier replaces [entity_token:entity_type:entity_id:field_name] with the value of the field specified.');

    return array_merge($information, parent::information());
  }
}
