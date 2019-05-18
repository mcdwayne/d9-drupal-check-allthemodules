<?php

namespace Drupal\automatic_updates\ReadinessChecker;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use DrupalFinder\DrupalFinder;
use Psr\Log\LoggerInterface;

/**
 * Modified code checker.
 */
class ModifiedCode implements ReadinessCheckerInterface {
  use StringTranslationTrait;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The Drupal root path.
   *
   * @var string
   */
  protected $drupalRoot;

  /**
   * The vendor path.
   *
   * @var string
   */
  protected $vendorPath;

  /**
   * The drupal finder service.
   *
   * @var \DrupalFinder\DrupalFinder
   */
  protected $drupalFinder;

  /**
   * ReadOnlyFilesystem constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   * @param \DrupalFinder\DrupalFinder $drupal_finder
   *   The Drupal finder.
   */
  public function __construct(LoggerInterface $logger, DrupalFinder $drupal_finder) {
    $this->logger = $logger;
    $this->drupalFinder = $drupal_finder;
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    $messages = [];
    if (!$this->getDrupalRoot()) {
      $messages[] = $this->t('The Drupal root directory could not be located.');
      return $messages;
    }
    $this->modifiedCode($messages);
    return $messages;
  }

  /**
   * Get the Drupal root path.
   *
   * @return string
   *   The Drupal root path.
   */
  protected function getDrupalRoot() {
    if (!$this->drupalRoot && $this->drupalFinder->locateRoot(getcwd())) {
      $this->drupalRoot = $this->drupalFinder->getDrupalRoot();
    }
    return $this->drupalRoot;
  }

  /**
   * Get the vendor path.
   *
   * @return string
   *   The vendor path.
   */
  protected function getVendorPath() {
    if (!$this->vendorPath && $this->drupalFinder->locateRoot(getcwd())) {
      $this->vendorPath = $this->drupalFinder->getVendorDir();
    }
    return $this->vendorPath;
  }

  /**
   * Check if the site contains any modified code.
   *
   * @param array $messages
   *   The messages array of translatable strings.
   */
  protected function modifiedCode(array &$messages) {
    // TODO: Implement file hashing logic against all code files.
    // See: https://www.drupal.org/project/automatic_updates/issues/3050804
  }

}
