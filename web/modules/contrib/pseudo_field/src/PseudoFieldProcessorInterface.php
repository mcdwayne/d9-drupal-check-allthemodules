<?php

namespace Drupal\pseudo_field;

/**
 * Interface PseudoFieldProcessorInterface
 */
interface PseudoFieldProcessorInterface {

  /**
   * Traverses the build and dispatches pseudo fields for transformation.
   *
   * @param array $build
   * @param \Drupal\Core\Entity\ContentEntityInterface $context
   *
   * @return array
   */
  public function process(array &$build, $context);

  /**
   * Transforms pseudo fields into real fields.
   *
   * @param array $element
   * @param array $context
   *
   * @return array
   */
  public function transform(array $element, $context);

  /**
   * Removes pseudo field related attributes from the element.
   *
   * @param array $element
   */
  public function cleanValues(array &$element);

}
