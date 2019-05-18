<?php

namespace Drupal\Tests\file_encrypt\Unit;

use Drupal\file_encrypt\PathProcessor\PathProcessorFiles;
use Symfony\Component\HttpFoundation\Request;

/**
 * @coversDefaultClass \Drupal\file_encrypt\PathProcessor\PathProcessorFiles
 * @group file_encrypt
 */
class PathProcessorFilesTest extends \PHPUnit_Framework_TestCase {

  /**
   * @dataProvider providerTestProcessInbound
   */
  public function testProcessInbound($incoming_path, Request $incoming_request, $expected_path, $expected_file_path = NULL) {
    $processor = new PathProcessorFiles();
    $result = $processor->processInbound($incoming_path, $incoming_request);
    $this->assertEquals($expected_path, $result);

    if (isset($expected_file_path)) {
      $this->assertEquals($expected_file_path, $incoming_request->query->get('file'));
    }
    else {
      $this->assertFalse($incoming_request->query->has('file'));
    }
  }

  /**
   * @see \Drupal\Tests\file_encrypt\Unit\PathProcessorFilesTest::testProcessInbound
   */
  public function providerTestProcessInbound() {
    $data = [];
    $data['non-file-path'] = ['/my-path', Request::create('/my-path'), '/my-path'];
    $data['encrypt-file-path'] = ['/encrypt/files/test.txt', Request::create('/encrypt/files/test.txt'), '/encrypt/files', 'test.txt'];
    $data['encrypt-file-path-with-folder'] = ['/encrypt/files/folder_a/folder_b/test.txt', Request::create('/encrypt/files/folder_a/folder_b/test.txt'), '/encrypt/files', 'folder_a/folder_b/test.txt'];
    $request = Request::create('/encrypt/files/test.txt');
    $request->query->set('file', 'test.txt');
    $data['encrypt-file-already-processed'] = ['/encrypt/files/test.txt', $request, '/encrypt/files/test.txt', 'test.txt'];
    return $data;
  }

}
