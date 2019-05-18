<?php

namespace Drupal\automatic_updates\ReadinessChecker;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use DrupalFinder\DrupalFinder;
use Psr\Log\LoggerInterface;

/**
 * Disk space checker.
 */
class DiskSpace extends Filesystem {
  use StringTranslationTrait;

  /**
   * Minimum disk space (in bytes) is 10mb.
   */
  const MINIMUM_DISK_SPACE = 10000000;

  /**
   * Megabyte divisor.
   */
  const MEGABYTE_DIVISOR = 1000000;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

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
  protected function doCheck() {
    return $this->diskSpaceCheck();
  }

  /**
   * Check if the filesystem has sufficient disk space.
   *
   * @return array
   *   An array of translatable strings if there is not sufficient space.
   */
  protected function diskSpaceCheck() {
    $messages = [];
    if (!$this->areSameLogicalDisk($this->getRootPath(), $this->getVendorPath())) {
      if (disk_free_space($this->getRootPath()) < static::MINIMUM_DISK_SPACE) {
        $messages[] = $this->t('Drupal root filesystem "@root" has insufficient space. There must be at least @space megabytes free.', [
          '@root' => $this->getRootPath(),
          '@space' => static::MINIMUM_DISK_SPACE / static::MEGABYTE_DIVISOR,
        ]);
      }
      if (disk_free_space($this->getVendorPath()) < static::MINIMUM_DISK_SPACE) {
        $messages[] = $this->t('Vendor filesystem "@vendor" has insufficient space. There must be at least @space megabytes free.', [
          '@vendor' => $this->getVendorPath(),
          '@space' => static::MINIMUM_DISK_SPACE / static::MEGABYTE_DIVISOR,
        ]);
      }
    }
    elseif (disk_free_space($this->getRootPath()) < static::MINIMUM_DISK_SPACE) {
      $messages[] = $this->t('Logical disk "@root" has insufficient space. There must be at least @space megabytes free.', [
        '@root' => $this->getRootPath(),
        '@space' => static::MINIMUM_DISK_SPACE / static::MEGABYTE_DIVISOR,
      ]);
    }
    return $messages;
  }

}
