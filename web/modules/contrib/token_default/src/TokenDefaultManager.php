<?php

namespace Drupal\token_default;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityInterface;

/**
 * Manager for populating tokens with defaults when not found.
 */
class TokenDefaultManager {

  /**
   * The entity type manager.
   *
   * @var Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Constructor for the token defaults manager.
   *
   * @param Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManager $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Add any tokens not replaced that have defaults to the replacements array.
   *
   * @param array $replacements
   *   Tokens which have found replacements.
   * @param array $context
   *   The context array containing all tokens and contextual data.
   *
   * @return array
   *   The full replacements array with any defaults added.
   */
  public function replaceMissingTokensWithDefaults(array $replacements, array $context) {
    $missingTokens = $this->getMissingTokens($replacements, $context['tokens']);
    $entity = $this->getEntity($context['data']);
    if ($entity && $missingTokens) {
      return $this->getReplacementsWithDefaults($replacements, $missingTokens, $entity);
    }
    return $replacements;
  }

  /**
   * Find the entity being used for the tokenizer.
   *
   * @param array $data
   *   The data section of the token context.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity used for the token.
   */
  protected function getEntity(array $data) {
    if (isset($data['entity'])) {
      return $data['entity'];
    }
    elseif (isset($data['token_type']) && isset($data[$data['token_type']])) {
      return $data[$data['token_type']];
    }
    return NULL;
  }

  /**
   * Find any tokens that should be replaced but cannot be found.
   *
   * @param array $replacements
   *   Tokens which have found replacements.
   * @param array $tokens
   *   All tokens found in the pattern.
   *
   * @return array
   *   Any tokens in the pattern but not substituted.
   */
  protected function getMissingTokens(array $replacements, array $tokens) {
    return array_diff(array_values($tokens), array_keys($replacements));
  }

  /**
   * Get the replacements with any defaults added.
   *
   * @param array $replacements
   *   The full array of replacements.
   * @param array $missingTokens
   *   Any tokens not replaced.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being used by the tokeniser.
   *
   * @return array
   *   The full replacements array.
   */
  protected function getReplacementsWithDefaults(array $replacements, array $missingTokens, EntityInterface $entity) {
    $tokenDefaultsStorage = $this->entityTypeManager->getStorage('token_default_token');
    $bundle = (method_exists($entity, 'bundle') ? $entity->bundle() : NULL);
    $properties = [
      'type' => $entity->getEntityTypeId(),
    ];
    foreach ($missingTokens as $token) {
      $tokenDefaults = $tokenDefaultsStorage->loadByProperties($properties + ['pattern' => $token]);
      foreach ($tokenDefaults as $tokenDefault) {
        $token_default_bundle = $tokenDefault->getBundle();
        if (empty($token_default_bundle) || $token_default_bundle == $bundle) {
          $replacements[$token] = $tokenDefault->getReplacement();
        }
      }
    }
    return $replacements;
  }

}
