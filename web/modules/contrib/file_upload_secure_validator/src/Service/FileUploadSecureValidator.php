<?php

namespace Drupal\file_upload_secure_validator\Service;

use Drupal\file\Entity\File;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Logger\LoggerChannelTrait;
use Symfony\Component\HttpFoundation\File\MimeType\FileinfoMimeTypeGuesser as SymfonyFileinfoMimeTypeGuesser;

/**
 * A service class for fileinfo-based validation.
 */
class FileUploadSecureValidator {

  // Copies/provides the t() function.
  use StringTranslationTrait;

  // Copies/provides the getLogger() function.
  use LoggerChannelTrait;

  /**
   * A csv mime descriptors equivalence group.
   */
  private $csvMimetypes = [
    'text/csv',
    'text/plain',
    'application/csv',
    'text/comma-separated-values',
    'application/excel',
    'application/vnd.ms-excel',
    'application/vnd.msexcel',
    'text/anytext',
    'application/octet-stream',
    'application/txt',
  ];

  /**
   * An xml mime descriptors equivalence group.
   */
  private $xmlMimetypes = [
    'text/xml',
    'text/plain',
    'application/xml',
  ];

  /**
   * File validation function.
   *
   * @param \Drupal\file\Entity\File $file
   *   The file to be uploaded.
   */
  public function validate(File $file) {
    $errors = [];

    // Get mime type from filename.
    $mime_by_filename = $file->getMimeType();
    // Get mime type from fileinfo.
    $symfony_fileinfo_mime_type_guesser = new SymfonyFileinfoMimeTypeGuesser();
    $mime_by_fileinfo = $symfony_fileinfo_mime_type_guesser->guess($file->getFileUri());

    // Early exit, fileinfo agrees with the file's extension.
    if ($mime_by_filename === $mime_by_fileinfo) {
      return [];
    }

    // Exit when a CSV mime-type equivalence is found.
    if (in_array($mime_by_filename, $this->csvMimetypes) && in_array($mime_by_fileinfo, $this->csvMimetypes)) {
      return [];
    }
    // Exit when an XML mime-type equivalence is found.
    if (in_array($mime_by_filename, $this->xmlMimetypes) && in_array($mime_by_fileinfo, $this->xmlMimetypes)) {
      return [];
    }
    // Handle disagreement.
    if ($mime_by_filename !== $mime_by_fileinfo) {
      $errors[] = $this->t('There was a problem with this file. The uploaded file is of type @extension but the real content seems to be @real_extension', ['@extension' => $mime_by_filename, '@real_extension' => $mime_by_fileinfo]);
      $this->getLogger('file_upload_secure_validator')
        ->error("Error while uploading file: MimeTypeGuesser guessed '%mime_by_fileinfo' and fileinfo '%mime_by_filename'",
          ['%mime_by_fileinfo' => $mime_by_fileinfo, '%mime_by_filename' => $mime_by_filename]
        );
    }
    return $errors;
  }

}
