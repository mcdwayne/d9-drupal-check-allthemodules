<?php

/**
 * @file
 * Contains access_unpublished token includes.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Entity\EntityPublishedInterface;

/**
 * Implements hook_token_info().
 */
function access_unpublished_token_info() {

  $types = [];
  $tokens = [];
  $definitions = \Drupal::entityTypeManager()->getDefinitions();
  foreach ($definitions as $definition) {
    if (in_array('Drupal\Core\Entity\EntityPublishedInterface', class_implements($definition->getClass()))) {
      $types[$definition->id()] = [
        'name' => t('Access Unpublished'),
        'description' => t('Tokens related to Access Unpublished.'),
        'needs-data' => $definition->id(),
      ];
      $tokens[$definition->id()] = [
        'access-unpublished-url' => [
          'name' => t('Access Unpublished URL'),
          'description' => t('The URL containing the hash. The most recently changed unexpired access token is used.'),
        ],
      ];
    }
  }

  return [
    'types' => $types,
    'tokens' => $tokens,
  ];
}

/**
 * Implements hook_tokens().
 */
function access_unpublished_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];

  if (!empty($data['entity']) && $data['entity'] instanceof EntityPublishedInterface) {
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $data['entity'];
    /** @var \Drupal\access_unpublished\AccessTokenManager $accessTokenManager */
    $accessTokenManager = \Drupal::service('access_unpublished.access_token_manager');
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'access-unpublished-url':
          $token = $accessTokenManager->getActiveAccessToken($entity);
          $url = '';
          if ($token) {
            $url = $accessTokenManager->getAccessTokenUrl($token);
          }

          $replacements[$original] = $url;
          break;
      }
    }
  }

  return $replacements;
}
