<?php

namespace Drupal\disrupt_tools\Service;

use Drupal\file\Plugin\Field\FieldType\FileFieldItemList;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;

/**
 * ImageStyleGenerator.
 *
 * Service to make it easy to generate Image Styles.
 */
class ImageStyleGenerator {
  /**
   * EntityTypeManagerInterface to load Image Styles.
   *
   * @var Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityImageStyle;

  /**
   * EntityTypeManagerInterface to load files.
   *
   * @var Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityFile;

  /**
   * Provides helpers to operate on files and stream wrappers.
   *
   * @var Drupal\Core\File\FileSystemInterface
   */
  private $fso;

  /**
   * Class constructor.
   */
  public function __construct(EntityTypeManagerInterface $entity, FileSystemInterface $fso) {
    $this->entityFile       = $entity->getStorage('file');
    $this->entityImageStyle = $entity->getStorage('image_style');
    $this->fso              = $fso;
  }

  /**
   * Generate Image Style, with responsive format.
   *
   * @param Drupal\file\Plugin\Field\FieldType\FileFieldItemList $field
   *   Field File Entity to Retrieve cover and generate it.
   * @param array $styles
   *   Styles to be generated.
   *
   * @return array
   *   Generated link of styles.
   */
  public function fromField(FileFieldItemList $field, array $styles) {
    $build = [];

    // Retrieve node.
    $cover_fid = '';

    if (isset($field) && isset($field->getValue()[0]) && isset($field->getValue()[0]['target_id'])) {
      $cover_fid = $field->getValue()[0]['target_id'];
    }

    if ($cover_fid) {
      $build = $this->styles($cover_fid, $styles);
    }

    return $build;
  }

  /**
   * Generate Image Style, with responsive format.
   *
   * @param int $fid
   *   File id to generate.
   * @param array $styles
   *   Styles to be generated.
   *
   * @return array
   *   Generated link of styles.
   */
  public function fromFile($fid, array $styles) {
    $build = [];

    $image = $this->entityFile->load($fid);

    if ($image) {
      $build = $this->styles($fid, $styles);
    }

    return $build;
  }

  /**
   * Generate Image Style URL, with responsive format.
   *
   * The Image Style URL given will be processed (derivated) by Drupal.
   *
   * @param int $fid
   *   File id to generated.
   * @param array $styles
   *   Styles to be generated.
   *
   * @return array
   *   Generated url of styles
   */
  protected function styles($fid, array $styles) {
    $build = [];
    $image = $this->entityFile->load($fid);

    if (empty($image)) {
      return $build;
    }

    // Check the image exist on the file system.
    $image_path = $this->fso->realpath($image->getFileUri());
    if (!$this->fileExist($image_path)) {
      return $build;
    }

    foreach ($styles as $media => $style) {
      $img_style = $this->entityImageStyle->load($style);
      if ($img_style) {
        $build[$media] = $img_style->buildUrl($image->getFileUri());
      }
    }

    return $build;
  }

  /**
   * Check file exist.
   *
   * @param string $path
   *   Path to the file.
   *
   * @return bool
   *   Returns TRUE if the file exists; FALSE otherwise.
   */
  protected function fileExist($path) {
    return file_exists($path);
  }

}
