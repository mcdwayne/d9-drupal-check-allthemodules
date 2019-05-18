<?php
/**
 * @file
 * Contains \Drupal\Tests\mailmute\Kernel\MailmuteKernelTestBase.
 */

namespace Drupal\Tests\mailmute\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Test base for Mailmute kernel tests.
 */
abstract class MailmuteKernelTestBase extends KernelTestBase {

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManager
   */
  protected $mailManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->mailManager = \Drupal::service('plugin.manager.mail');
    \Drupal::configFactory()->getEditable('system.mail')
      ->set('interface', ['default' => 'test_mail_collector'])
      ->save();
  }

}
