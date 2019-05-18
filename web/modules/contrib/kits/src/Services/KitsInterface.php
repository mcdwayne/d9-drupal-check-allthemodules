<?php

namespace Drupal\kits\Services;

/**
 * Interface KitsInterface
 *
 * @package Drupal\kits\Services
 */
interface KitsInterface {
  /**
   * Returns the currently active global container.
   *
   * @return \Symfony\Component\DependencyInjection\ContainerInterface|null
   */
  public function getContainer();

  /**
   * @param string $string
   * @param array $args
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   */
  public function t($string, $args = []);
}
