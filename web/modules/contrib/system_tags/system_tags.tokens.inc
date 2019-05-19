<?php

/**
 * @file
 * system_tags.tokens.inc.
 */

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function system_tags_token_info() {
  $types = $tokens = [];

  $types['system_tags'] = [
    'name' => t('System Tags'),
    'description' => t('Tokens related to System Tags'),
  ];

  $storage = \Drupal::entityTypeManager()->getStorage('system_tag');
  $map = \Drupal::service('system_tags.system_tag_helper')
    ->getFieldMap();
  foreach (array_keys($map) as $entity_type) {
    foreach ($storage->loadMultiple() as $tag_id => $system_tag) {
      $token_id = sprintf('%s--%s', $entity_type, $tag_id);
      $tokens['system_tags'][$token_id] = [
        'name' => $system_tag->label(),
        'description' => t("The URL for the entity marked with the tag '@system_tag'. The token will look after an entity of the same type as the source.", [
          '@system_tag' => $tag_id,
        ]),
      ];
    }
  }

  return compact('types', 'tokens');
}

/**
 * Implements hook_tokens().
 */
function system_tags_tokens($type, array $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];

  if ($type === 'system_tags') {
    // Find the data entity.
    $data_entity = NULL;
    foreach ($data as $key => $value) {
      if ($value instanceof EntityInterface) {
        $data_entity = $value;
      }
    }

    /** @var \Drupal\Core\Path\AliasManagerInterface $path_alias_manager */
    $path_alias_manager = \Drupal::service('path.alias_manager');
    foreach ($tokens as $token_id => $original) {
      list($entity_type, $tag_id) = explode('--', $token_id);

      try {
        /** @var \Drupal\system_tags\SystemTagFinder\SystemTagFinderInterface $system_tag_finder */
        $system_tag_finder = \Drupal::service('plugin.manager.system_tags.system_tag_finder_manager')
          ->getInstance(compact('entity_type'));
      }
      catch (PluginException $e) {
        watchdog_exception('system_tags', $e);
        continue;
      }

      if ($entity = $system_tag_finder->findOneByTag($tag_id)) {
        // Replace the original token with the translated alias.
        $replacements[$original] = $path_alias_manager->getAliasByPath(
          sprintf('/%s', $entity->toUrl()->getInternalPath()),
          $data_entity->language()->getId()
        );
      }
    }
  }

  return $replacements;
}
