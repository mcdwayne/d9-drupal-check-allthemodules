<?php

namespace Drupal\Tests\uc_file\Functional;

use Drupal\Tests\Traits\Core\CronRunTrait;

/**
 * Tests the file purchase functionality.
 *
 * @group ubercart
 */
class FileTest extends FileTestBase {
  use CronRunTrait;

  /**
   * Tests that purchased files may be downloaded after checkout.
   */
  public function testFilePurchaseCheckout() {
    // Add file download feature to the test product.
    $filename = $this->getTestFile();
    $this->drupalLogin($this->adminUser);
    $this->drupalPostForm('node/' . $this->product->id() . '/edit/features', ['feature' => 'file'], 'Add');
    $edit = [
      'uc_file_model' => '',
      'uc_file_filename' => $filename,
    ];
    $this->drupalPostForm(NULL, $edit, 'Save feature');

    // Check out with the test product.
    $method = $this->createPaymentMethod('other');
    $this->addToCart($this->product);
    $order = $this->checkout();
    uc_payment_enter($order->id(), 'other', $order->getTotal());

    // Test that the file was granted.
    // @todo Re-enable when Rules is available.
    // $this->assertTrue($order->getUser()->hasFile($filename), 'Existing user was granted file.');

    // Test that the email is correct.
//    $file = entity_load('user_file', $fid);

    // @todo Re-enable when Rules is available.
    // $this->assertMailString('subject', $file->label(), 4, 'File assignment email mentions file in subject line.');

    // Delete the user.
    user_delete($order->getOwnerId());

    // Run cron to ensure deleted users are handled correctly.
    $this->cronRun();
  }

}
