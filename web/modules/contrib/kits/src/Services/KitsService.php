<?php

namespace Drupal\kits\Services;

use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class KitsService
 *
 * @package Drupal\kits\Services
 */
class KitsService implements KitsInterface {
  /**
   * @var ContainerInterface
   */
  private $container;

  /**
   * KitsService constructor.
   */
  public function __construct() {
    // TODO: inject this, if possible
    $this->container = \Drupal::getContainer();
  }

  /**
   * @inheritdoc
   */
  public function getContainer() {
    return $this->container;
  }

  /**
   * @return TranslationInterface
   */
  private function getTranslationService() {
    /** @var TranslationInterface $service */
    static $service;
    if (NULL === $service) {
      $service = $this->container->get('string_translation');
    }
    return $service;
  }

  /**
   * @inheritdoc
   */
  public function t($string, $args = []) {
    return $this->getTranslationService()->translate($string, $args);
  }
}
