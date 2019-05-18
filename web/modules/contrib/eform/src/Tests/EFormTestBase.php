<?php

namespace Drupal\eform\Tests;

use Drupal\Core\Session\AccountInterface;
use Drupal\simpletest\WebTestBase;

abstract class EFormTestBase extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('eform');

  /**
   * @var \Drupal\Core\Entity\EntityAccessControlHandlerInterface
   */
  protected $accessTypeHandler;

  /**
   * @var \Drupal\Core\Entity\EntityAccessControlHandlerInterface
   */
  protected $accessSubmissionHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->accessTypeHandler = \Drupal::entityTypeManager()->getAccessControlHandler('eform_type');
    $this->accessSubmissionHandler = \Drupal::entityTypeManager()->getAccessControlHandler('eform_submission');
  }

  /**
   * Asserts that eform type creation access correctly grants or denies access.
   *
   * @param bool $result
   * @param \Drupal\Core\Session\AccountInterface $account
   * @param string|null $langcode
   */
  function assertEFormTypeCreateAccess($result, AccountInterface $account, $langcode = NULL) {
    $this->assertEqual($result, $this->accessTypeHandler->createAccess(NULL, $account, array(
      'langcode' => $langcode,
    )));
  }

}
