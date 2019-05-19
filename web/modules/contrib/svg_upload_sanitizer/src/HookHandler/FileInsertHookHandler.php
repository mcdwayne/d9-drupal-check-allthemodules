<?php

namespace Drupal\svg_upload_sanitizer\HookHandler;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\file\FileInterface;
use Drupal\svg_upload_sanitizer\Helper\FileHelper;
use Drupal\svg_upload_sanitizer\Helper\SanitizerHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Hook handler for the file_insert() hook.
 *
 * @package Drupal\svg_upload_sanitizer\Hook
 */
class FileInsertHookHandler implements ContainerInjectionInterface {

  /**
   * The sanitizer helper.
   *
   * @var \Drupal\svg_upload_sanitizer\Helper\SanitizerHelper
   */
  private $sanitizerHelper;

  /**
   * The file helper.
   *
   * @var \Drupal\svg_upload_sanitizer\Helper\FileHelper
   */
  private $fileHelper;

  /**
   * FileInsertHookHandler constructor.
   *
   * @param \Drupal\svg_upload_sanitizer\Helper\SanitizerHelper $sanitizer_helper
   *   The optimizer helper.
   * @param \Drupal\svg_upload_sanitizer\Helper\FileHelper $file_helper
   *   The file helper.
   */
  public function __construct(SanitizerHelper $sanitizer_helper, FileHelper $file_helper) {
    $this->sanitizerHelper = $sanitizer_helper;
    $this->fileHelper = $file_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('svg_upload_sanitizer.helper.sanitizer'),
      $container->get('svg_upload_sanitizer.helper.file')
    );
  }

  /**
   * Try to sanitize the inserted file.
   *
   * @param \Drupal\file\FileInterface $file
   *   The file.
   *
   * @return bool
   *   TRUE if the file was sanitized, FALSE otherwise.
   *
   * @throws \Exception
   */
  public function process(FileInterface $file) {
    if (!$this->sanitizerHelper->sanitize($file)) {
      return FALSE;
    }

    return $this->fileHelper->updateSize($file);
  }

}
