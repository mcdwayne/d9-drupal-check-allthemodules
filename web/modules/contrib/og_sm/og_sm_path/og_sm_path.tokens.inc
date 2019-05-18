<?php

/**
 * @file
 * Custom tokens.
 */

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\node\NodeInterface;
use Drupal\og_sm\OgSm;
use Drupal\og_sm_path\OgSmPath;

/**
 * Implements hook_token_info().
 */
function og_sm_path_token_info() {
  $entity_tokens = [];
  $entity_tokens['site-path'] = [
    'name' => t('Site Path'),
    'description' => t('The path of the Site the entity belongs to.'),
  ];

  return [
    'tokens' => [
      'node' => $entity_tokens,
      'term' => $entity_tokens,
    ],
  ];
}

/**
 * Implements hook_tokens().
 */
function og_sm_path_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];

  switch ($type) {
    case 'node':
    case 'term':
      $replacements = _og_sm_path_tokens_entity($tokens, $data[$type], $options, $bubbleable_metadata);
      break;
  }

  return $replacements;
}

/**
 * Tokens for nodes.
 *
 * @param array $tokens
 *   An array of tokens to be replaced. The keys are the machine-readable token
 *   names, and the values are the raw [type:token] strings that appeared in the
 *   original text.
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity to create the token for.
 * @param array $options
 *   An associative array of options for token replacement; see
 *   \Drupal\Core\Utility\Token::replace() for possible values.
 * @param \Drupal\Core\Render\BubbleableMetadata $bubbleable_metadata
 *   The bubbleable metadata.
 *
 * @return array
 *   Replacements keyed by their token.
 */
function _og_sm_path_tokens_entity(array $tokens, EntityInterface $entity, array $options, BubbleableMetadata $bubbleable_metadata) {
  $sanitize = !empty($options['sanitize']);

  // Create the replacements.
  $replacements = [];
  foreach ($tokens as $name => $original) {
    switch ($name) {
      case 'site-path':
        $replacements[$original] = _og_sm_path_tokens_site_path($entity, $sanitize);
        $bubbleable_metadata->addCacheableDependency($entity);
        break;
    }
  }

  // Filter out empty values.
  return array_filter($replacements);
}

/**
 * Get the path of the Site the node belongs to.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity to create the token for.
 * @param bool $sanitize
 *   Should the output be sanitized.
 *
 * @return string|null
 *   Token string if any.
 */
function _og_sm_path_tokens_site_path(EntityInterface $entity, $sanitize) {
  $site_manager = OgSm::siteManager();

  $site = FALSE;
  if ($entity instanceof NodeInterface && $site_manager->isSite($entity)) {
    $site = $entity;
  }
  if (!$site) {
    $site = $site_manager->getSiteFromEntity($entity);
  }

  if (!$site) {
    return NULL;
  }

  $path = OgSmPath::sitePathManager()->getPathFromSite($site);
  return $sanitize ? Html::escape($path) : $path;
}
