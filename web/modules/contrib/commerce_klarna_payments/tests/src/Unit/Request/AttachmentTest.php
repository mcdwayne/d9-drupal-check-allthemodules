<?php

namespace Drupal\Tests\commerce_klarna_payments\Unit\Request;

use Drupal\commerce_klarna_payments\Klarna\Data\Payment\AttachmentItemInterface;
use Drupal\commerce_klarna_payments\Klarna\Request\Payment\Attachment;
use Drupal\Tests\UnitTestCase;

/**
 * Attachment request unit tests.
 *
 * @group commerce_klarna_payments
 * @coversDefaultClass \Drupal\commerce_klarna_payments\Klarna\Request\Payment\Attachment
 */
class AttachmentTest extends UnitTestCase {

  /**
   * @covers ::setContentType
   * @covers ::setBody
   */
  public function testAttachment() {
    $item = $this->getMockBuilder(AttachmentItemInterface::class)
      ->getMock();

    // We don't have value object for AttachmentItemInterface
    // so we have to mock it.
    $item->expects($this->once())
      ->method('toArray')
      ->willReturn([
        'air_reservation_details' => [
          'pnr' => '123',
        ],
      ]);

    $attachment = new Attachment();
    $attachment->setContentType('test')
      ->setBody($item);

    $this->assertEquals([
      'content_type' => 'test',
      'body' => '{"air_reservation_details":{"pnr":"123"}}',
    ], $attachment->toArray());
  }

}
