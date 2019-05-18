<?php

namespace Drupal\Tests\mimemail\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\mimemail\Utility\MimeMailFormatHelper;

/**
 * Tests that Mime Mail utility functions work properly.
 *
 * @coversDefaultClass \Drupal\mimemail\Utility\MimeMailFormatHelper
 *
 * @group mimemail
 */
class MimeMailKernelTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var string[]
   */
  public static $modules = [
    'mailsystem',
    'mimemail',
  ];

  /**
   * Tests the regular expression for extracting the mail address.
   *
   * @covers ::mimeMailHeaders
   */
  public function testHeaders() {
    $chars = ['-', '.', '+', '_'];
    $name = $this->randomString();
    $local = $this->randomMachineName() . $chars[array_rand($chars)] . $this->randomMachineName();
    $domain = $this->randomMachineName() . '-' . $this->randomMachineName() . '.' . $this->randomMachineName(rand(2, 4));
    $headers = MimeMailFormatHelper::mimeMailHeaders([], "$name <$local@$domain>");
    $result = $headers['Return-Path'];
    $expected = "<$local@$domain>";
    $this->assertSame($result, $expected, 'Return-Path header field correctly set.');
  }

  /**
   * Tests helper function for formattting URLs.
   *
   * @covers ::mimeMailUrl
   */
  public function testUrl() {
    $result = MimeMailFormatHelper::mimeMailUrl('#');
    $this->assertSame($result, '#', 'Hash mark URL without fragment left intact.');

    $url = '/sites/default/files/styles/thumbnail/public/image.jpg?itok=Wrl6Qi9U';
    $result = MimeMailFormatHelper::mimeMailUrl($url, TRUE);
    $expected = '/sites/default/files/styles/thumbnail/public/image.jpg';
    $this->assertSame($result, $expected, 'Security token removed from styled image URL.');

    $expected = $url = 'public://' . $this->randomMachineName() . ' ' . $this->randomMachineName() . '.' . $this->randomMachineName(3);
    $result = MimeMailFormatHelper::mimeMailUrl($url, TRUE);
    $this->assertSame($result, $expected, 'Space in the filename of the attachment left intact.');
  }

}
