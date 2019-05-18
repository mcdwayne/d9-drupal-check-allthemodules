<?php

namespace Drupal\entity_slug\Plugin\Slugifier;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Core\Entity\FieldableEntityInterface;

/**
 * Provides an interface describing the a slugifier plugin.
 */
interface SlugifierInterface extends ConfigurablePluginInterface {

  /**
   * Slugifies the input, turning it into a URL-friendly string.
   *
   * @param string $input
   *  The input string to slugify.
   * @param FieldableEntityInterface $entity
   *  The entity the slug is for.
   *
   * @return string
   *  The processed URL-friendly slug.
   */
  public function slugify($input, FieldableEntityInterface $entity);

  /**
   * Returns an array of informational strings for display in a list of
   * instructions.
   *
   * @return string[]
   *   An array of informational strings.
   */
  public function information();
}
