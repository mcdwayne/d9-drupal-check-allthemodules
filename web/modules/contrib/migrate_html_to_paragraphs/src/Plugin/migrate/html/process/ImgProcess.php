<?php

namespace Drupal\migrate_html_to_paragraphs\Plugin\migrate\html\process;

use Drupal\Component\Utility\UrlHelper;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Migration HTML - img processor.
 *
 * @MigrateHtmlProcessPlugin(
 *   id = "html_process_img"
 * )
 */
class ImgProcess extends HtmlTagImgProcess {

  /**
   * The machine name of the image field of the paragraph.
   *
   * @var string|null
   */
  protected $fieldName = NULL;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->setBundle('image');
    if (isset($this->configuration['bundle'])) {
      $this->setBundle($this->configuration['bundle']);
    }

    $this->setFieldName('field_image');
    if (isset($this->configuration['field_name'])) {
      $this->setFieldName($this->configuration['field_name']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function process(MigrateExecutableInterface $migrate_executable, array $tag) {
    $this->migrateExecutable = $migrate_executable;

    // Processing the file.
    $source = $tag['src'];

    // Check if the image contains Base64 data instead of a URL.
    if (preg_match('/data:image\/(\w+);base64,(.*)/i', $source, $matches)) {
      // Decode the real base64 value.
      $data = $matches[2];
      // Get the mime type.
      $mime_type = $matches[1];
      $file = $this->processImageBase64($data, $mime_type);
    }
    // Processing the image source URL.
    else {
      $file = $this->processImageUrl($source);
    }

    if (is_subclass_of($file, '\Drupal\file\FileInterface')) {
      $this->setFile($file);

      // Processing the alt attribute.
      if (isset($tag['alt']) && !empty($tag['alt'])) {
        $this->setAlt($tag['alt']);
      }

      // Processing the title attribute.
      if (isset($tag['title']) && !empty($tag['title'])) {
        $this->setTitle($tag['title']);
      }

      return TRUE;
    }

    $this->logMessage(
      t('Something went wrong with the source file %source', [
        '%source' => $source,
      ]),
      MigrationInterface::MESSAGE_ERROR
    );

    return FALSE;
  }

  /**
   * Helper function to process the Base64 data of the image.
   *
   * @param string $data
   *   The data string.
   * @param string $mimeType
   *   The mime type of the file.
   *
   * @return FileInterface|false
   *   The file object or false if not succeeded.
   */
  protected function processImageBase64($data, $mimeType) {
    // Decode the real base64 value.
    $data = base64_decode($data);
    // Extension based on mime type (replacing jpeg by jpg).
    $ext = ($mimeType == 'jpeg') ? 'jpg' : $mimeType;
    // Target folder.
    $target_folder = $this->getTargetFolder();
    // Create the file.
    return $this->createFile($data, $target_folder . '/image.' . $ext);
  }

  /**
   * Helper function to process the Base64 data of the image.
   *
   * @param string $source
   *   The source URL of the file.
   *
   * @return FileInterface|false
   *   The file object or false if not succeeded.
   */
  protected function processImageUrl($source) {
    $file = FALSE;

    // Source base path.
    $source_base_path = $this->getSourceBasePath();
    // Source base URL.
    $source_base_urls = $this->getSourceBaseUrls();
    // Target folder.
    $target_folder = $this->getTargetFolder();

    if ($source_base_urls) {
      try {
        $external_is_local_url = FALSE;

        // Let's test if the source URL has a variant which is a local URL.
        foreach ($source_base_urls as $source_base_url) {
          if (UrlHelper::externalIsLocal($source, $source_base_url)) {
            $external_is_local_url = $source_base_url;
            break;
          }
        }

        // If the source URL is a local URL.
        if ($external_is_local_url) {
          // Strip the source base website URL from the source URL.
          $source = str_ireplace($external_is_local_url, '', $source);
        }
      }
      catch (\InvalidArgumentException $e) {
      }
    }

    // Check if the source URL is an external URL.
    if (UrlHelper::isExternal($source)) {
      // Check if the source is a valid URL.
      if (UrlHelper::isValid($source, FALSE)) {
        $file = $this->createFileByUri($source, $target_folder);
      }
    }
    // From now on, we expect the source URL to be
    // a relative path to a file on the source.
    elseif ($source_base_path) {
      // Prepend the source base path to the source URL.
      $file_uri = $source_base_path . $source;
      // Check if the URL is valid, this tells us the path is still a URL.
      // So we need to create a file by it's URL.
      if (UrlHelper::isValid($file_uri, TRUE)) {
        $file = $this->createFileByUri($file_uri, $target_folder);
      }
      // In other cases, we expect the path to be an absolute path to the file.
      // So we need to copy the file by it's absolute path.
      else {
        $file = $this->copyFile($file_uri, $target_folder);
      }
    }

    return $file;
  }

  /**
   * {@inheritdoc}
   */
  public function createParagraph($value) {
    if ($fid = $this->getFileId()) {
      $paragraph = Paragraph::create([
        'id'                  => NULL,
        'type'                => $this->getBundle(),
        $this->getFieldName() => [
          'target_id' => $fid,
          'alt'       => $this->getAlt(),
          'title'     => $this->getTitle(),
        ],
      ]);
      $paragraph->save();

      return $paragraph;
    }

    return NULL;
  }

  /**
   * Return the machine name of the image field of the paragraph.
   *
   * @return string|null
   *   The machine name of the image field of the paragraph or null if not set.
   */
  public function getFieldName() {
    return $this->fieldName;
  }

  /**
   * Set the machine name of the image field of the paragraph.
   *
   * @param string $fieldName
   *   The machine name of the image field of the paragraph.
   */
  protected function setFieldName($fieldName) {
    $this->fieldName = $fieldName;
  }

}
