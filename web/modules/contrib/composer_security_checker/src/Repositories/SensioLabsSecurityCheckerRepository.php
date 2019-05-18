<?php

namespace Drupal\composer_security_checker\Repositories;

use Drupal\composer_security_checker\Collections\AdvisoryCollection;
use Drupal\composer_security_checker\Parsers\SensioLabsSecurityCheckerParser;
use Drupal\Core\Config\ConfigFactoryInterface;
use SensioLabs\Security\SecurityChecker;

/**
 * Class SensioLabsSecurityCheckerRepository.
 *
 * @package Drupal\composer_security_checker
 */
class SensioLabsSecurityCheckerRepository implements RepositoryInterface {

  /**
   * Path to the Composer lock file.
   *
   * @var string
   */
  protected $composerLockfilePath;

  /**
   * SensioLabs security checker.
   *
   * @var SecurityChecker
   */
  private $checker;

  /**
   * A collection of security advisories.
   *
   * @var \Drupal\composer_security_checker\Collections\AdvisoryCollection
   */
  private $collection;

  /**
   * SensioLabsSecurityCheckerRepository constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param SecurityChecker $checker
   *   A collection of security advisories.
   */
  public function __construct(ConfigFactoryInterface $config_factory, SecurityChecker $checker) {
    $this->composerLockfilePath = $config_factory->get('composer_security_checker.settings')->get('composer_lock_file_path');
    $this->checker = $checker;
    $this->collection = new AdvisoryCollection();
  }

  /**
   * Get the raw response from the SensioLabs Security Checker.
   *
   * @return array
   *   An array of security vulnerabilities.
   */
  private function getRawUpdates() {
    return $this->checker->check($this->composerLockfilePath);
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableUpdates() {
    foreach ($this->getRawUpdates() as $raw_update_title => $raw_update) {
      $parser = new SensioLabsSecurityCheckerParser($raw_update_title, $raw_update);
      $parsed_response = $parser->parse();
      $this->collection->ingest($parsed_response);
    }
    return $this->collection;
  }

}
