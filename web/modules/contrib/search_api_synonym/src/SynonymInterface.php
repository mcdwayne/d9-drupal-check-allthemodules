<?php

namespace Drupal\search_api_synonym;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Synonym entities.
 *
 * @ingroup search_api_synonym
 */
interface SynonymInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {
  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Synonym type.
   *
   * @return string
   *   Type of the Synonym.
   */
  public function getType();

  /**
   * Sets the Synonym type.
   *
   * @param string $type
   *   The Synonym type.
   *
   * @return \Drupal\search_api_synonym\SynonymInterface
   *   The called Synonym entity.
   */
  public function setType($type);

  /**
   * Gets the Synonym word.
   *
   * @return string
   *   Word of the Synonym.
   */
  public function getWord();

  /**
   * Sets the Synonym word.
   *
   * @param string $word
   *   The Synonym word.
   *
   * @return \Drupal\search_api_synonym\SynonymInterface
   *   The called Synonym entity.
   */
  public function setWord($word);

  /**
   * Gets the synonyms.
   *
   * @return string
   *   The synonyms to the word.
   */
  public function getSynonyms();

  /**
   * Gets the synonyms formatted.
   *
   * Format the comma separated synonyms string with extra spaces.
   *
   * @return string
   *   The synonyms to the word.
   */
  public function getSynonymsFormatted();

  /**
   * Sets the synonyms to the word.
   *
   * @param string $synonyms
   *   The synonyms.
   *
   * @return \Drupal\search_api_synonym\SynonymInterface
   *   The called Synonym entity.
   */
  public function setSynonyms($synonyms);

  /**
   * Gets the Synonym creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Synonym.
   */
  public function getCreatedTime();

  /**
   * Sets the Synonym creation timestamp.
   *
   * @param int $timestamp
   *   The Synonym creation timestamp.
   *
   * @return \Drupal\search_api_synonym\SynonymInterface
   *   The called Synonym entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Synonym active status indicator.
   *
   * Not active synonyms are not used by the search engine.
   *
   * @return bool
   *   TRUE if the Synonym is active.
   */
  public function isActive();

  /**
   * Sets the active status of a Synonym.
   *
   * @param bool $active
   *   TRUE to set this Synonym to active, FALSE to set it to not active.
   *
   * @return \Drupal\search_api_synonym\SynonymInterface
   *   The called Synonym entity.
   */
  public function setActive($active);

}
