<?php

namespace Drupal\devel_snippet;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Devel Snippet entities.
 *
 * @ingroup devel_snippet
 */
interface DevelSnippetInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Devel Snippet name.
   *
   * @return string
   *   Name of the Devel Snippet.
   */
  public function getName();

  /**
   * Sets the Devel Snippet name.
   *
   * @param string $name
   *   The Devel Snippet name.
   *
   * @return \Drupal\devel_snippet\DevelSnippetInterface
   *   The called Devel Snippet entity.
   */
  public function setName($name);

  /**
   * Gets the Devel Snippet creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Devel Snippet.
   */
  public function getCreatedTime();

  /**
   * Sets the Devel Snippet creation timestamp.
   *
   * @param int $timestamp
   *   The Devel Snippet creation timestamp.
   *
   * @return \Drupal\devel_snippet\DevelSnippetInterface
   *   The called Devel Snippet entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the Devel Snippet code.
   *
   * @return string
   *   Devel Snippet code.
   */
  public function getCode();

  /**
   * Sets the Devel Snippet code.
   *
   * @param string $code
   *   Devel Snippet code.
   *
   * @return \Drupal\devel_snippet\DevelSnippetInterface
   *   The called Devel Snippet entity.
   */
  public function setCode($code);

}
