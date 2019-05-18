<?php

namespace Drupal\entity_slug\Plugin\Slugifier;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Path\AliasManager;
use Drupal\entity_slug\Annotation\Slugifier;
use Drupal\taxonomy\TermInterface;

/**
 * @Slugifier(
 *   id = "entity_alias",
 *   name = @Translation("Entity alias replacer"),
 *   weight = -60,
 * )
 */
class EntityAliasSlugifier extends SlugifierBase {

  /**
   * {@inheritdoc}
   */
  public function slugify($input, FieldableEntityInterface $entity) {
    $output = $input;

    while (preg_match('/\[entity_alias:([^:\]]+):([^\]]+)\]/', $output, $matches)) {
      list($match, $entity_type, $entity_id) = $matches;

      $replacement = '';

      /** @var FieldableEntityInterface $entity */
      $matched_entity = \Drupal::entityTypeManager()->getStorage($entity_type)->load($entity_id);

      if ($matched_entity) {
        /** @var \Drupal\Core\Path\AliasManagerInterface $alias_manager */
        $alias_manager = \Drupal::service('path.alias_manager');
        $language = $entity->language()->getId();
        $internal_path = '/' . $matched_entity->toUrl()->getInternalPath();
        $replacement = $alias_manager->getAliasByPath($internal_path, $language);

        if ($internal_path == $replacement) {
          $replacement = $alias_manager->getAliasByPath($internal_path, \Drupal::languageManager()->getDefaultLanguage()->getId());
        }

        if ($internal_path == $replacement) {
          $replacement = '';
        }
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

    $information[] = $this->t('The Entity Alias slugifier replaces [entity_alias:entity_type:entity_id] with the value of the alias of the specified entity.');

    return array_merge($information, parent::information());
  }
}
