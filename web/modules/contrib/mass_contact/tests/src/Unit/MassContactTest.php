<?php

namespace Drupal\Tests\mass_contact\Unit;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Session\AccountInterface;
use Drupal\mass_contact\MassContact;
use Drupal\mass_contact\OptOutInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for the Mass Contact helper service.
 *
 * @group mass_contact
 *
 * @coversDefaultClass \Drupal\mass_contact\MassContact
 */
class MassContactTest extends UnitTestCase {

  /**
   * Tests html support detection.
   *
   * @covers ::htmlSupported
   */
  public function testHtmlSupported() {
    // Test for no modules supporting html email.
    $module_handler = $this->prophesize(ModuleHandlerInterface::class);
    $module_handler->moduleExists('mimemail')->willReturn(FALSE);
    $module_handler->moduleExists('swiftmailer')->willReturn(FALSE);
    $config_factory = $this->prophesize(ConfigFactoryInterface::class)->reveal();
    $queue_factory = $this->prophesize(QueueFactory::class)->reveal();
    $mail_manager = $this->prophesize(MailManagerInterface::class)->reveal();
    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class)->reveal();
    $opt_out = $this->prophesize(OptOutInterface::class)->reveal();
    $account = $this->prophesize(AccountInterface::class)->reveal();
    $fixture = new MassContact($module_handler->reveal(), $config_factory, $queue_factory, $mail_manager, $entity_type_manager, $opt_out, $account);
    $this->assertFalse($fixture->htmlSupported());

    // Mime mail module.
    $module_handler->moduleExists('mimemail')->willReturn(TRUE);
    $fixture = new MassContact($module_handler->reveal(), $config_factory, $queue_factory, $mail_manager, $entity_type_manager, $opt_out, $account);
    $this->assertTrue($fixture->htmlSupported());

    // Swiftmailer module.
    $module_handler->moduleExists('mimemail')->willReturn(FALSE);
    $module_handler->moduleExists('swiftmailer')->willReturn(TRUE);
    $fixture = new MassContact($module_handler->reveal(), $config_factory, $queue_factory, $mail_manager, $entity_type_manager, $opt_out, $account);
    $this->assertTrue($fixture->htmlSupported());
  }

}
