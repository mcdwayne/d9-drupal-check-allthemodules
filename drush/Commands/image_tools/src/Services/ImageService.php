<?php

namespace Drupal\image_tools\Services;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystem;
use Drupal\Core\Database\Connection;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;

/**
 * Service Class for Image resizing and conversion.
 */
class ImageService {

  const IMAGE_TOOLS_DEFAULT_MAX_WIDTH = 2048;

  /**
   * Filesystem.
   *
   * @var \Drupal\Core\File\FileSystem
   */
  private $filesystem;

  /**
   * EntityManager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityManager;

  /**
   * DB Connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  private $db;

  /**
   * DrushImageCommandsCommands constructor.
   *
   * @param \Drupal\Core\File\FileSystem $filesystem
   *   Filesystem.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityManager
   *   EntityManager.
   * @param \Drupal\Core\Database\Connection $db
   *   DB Connection.
   */
  public function __construct(FileSystem $filesystem, EntityTypeManagerInterface $entityManager, Connection $db) {
    $this->filesystem = $filesystem;
    $this->entityManager = $entityManager;
    $this->db = $db;
  }

  /**
   * Load PNG Images from the File Storage and pre check if they have a transparency.
   *
   * @return array
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function loadPngImages() {
    $file_storage = $this->entityManager->getStorage('file');

    $result = $file_storage->loadByProperties(['filemime' => 'image/png']);

    $files = [];
    foreach ($result as $file) {
      /** @var \Drupal\file\Entity\File $file */
      if (strpos($file->getFileUri(), 'media-icons') !== FALSE) {
        unset($file);
        continue;
      }

      $image_path = $this->filesystem->realpath($file->getFileUri());
      $t = $this->detectTransparency($image_path);

      $fid = $this->getFid($file);
      $files[$fid] = [
        'file' => $file,
        'path' => $image_path,
        'transparency' => $t,
      ];
    }

    return $files;
  }

  /**
   * Convert Png Images to JPG Images.
   *
   * @param array $files
   *   Files Array with informations about the Image.
   *
   * @return array
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function convertPngImagesToJpeg(array $files) {
    $current_size = 0;
    $new_size = 0;
    $images_converted = 0;
    foreach ($files as $fid => $element) {
      if (!file_exists($element['path'])) {
        continue;
      }
      if ($element['transparency']) {
        continue;
      }

      $current_size += filesize($element['path']);
      $new_path = $this->convertPngToJpeg($element['path'], $element['file']);
      $new_size += filesize($new_path);

      $images_converted++;
    }

    $current_size = round($current_size / 1024 / 1024, 2);
    $new_size = round($new_size / 1024 / 1024, 2);

    return [
      $images_converted,
      $current_size,
      $new_size,
      $current_size - $new_size,
    ];
  }

  /**
   * Convert all Images with type png to jpg.
   *
   * @param string $path
   *   The path to the Image File.
   * @param \Drupal\file\Entity\File $file
   *   File Object.
   *
   * @return string
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function convertPngToJpeg($path, File $file) {
    $image_path = dirname($path);
    $image_name_jpg = preg_replace('"\.png$"', '.jpg', $file->getFilename());
    $image_path_jpg = $image_path . DIRECTORY_SEPARATOR . $image_name_jpg;

    $this->gdPngToJpg($path, $image_path_jpg);

    $file->setFileUri(preg_replace('"\.png$"', '.jpg', $file->getFileUri()));
    $file->setFilename($image_name_jpg);
    $file->setMimeType('image/jpeg');

    $file->save();
    unlink($path);

    return $image_path_jpg;
  }

  /**
   * Search for image with a size larger then max_width.
   *
   * @param int $max_width
   *   Max width.
   * @param bool $include_png
   *   Include Pngs.
   *
   * @return array
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function findLargeWidthImages($max_width, $include_png) {
    $file_storage = $this->entityManager->getStorage('file');
    $result = $file_storage->loadByProperties(['filemime' => 'image/jpeg']);

    if ($include_png) {
      $pngs = $file_storage->loadByProperties(['filemime' => 'image/png']);
      $result = array_merge($result, $pngs);
    }

    $files = [];
    foreach ($result as $file) {
      /** @var \Drupal\file\Entity\File $file */
      $image_path = $this->filesystem->realpath($file->getFileUri());

      if (!file_exists($image_path)) {
        continue;
      }

      list($width, $height, $type) = getimagesize($image_path);

      if ($width > $max_width) {
        $fid = $this->getFid($file);
        $files[$fid] = [
          'file' => $file,
          'path' => $image_path,
          'width' => $width,
          'height' => $height,
        ];

        if ($type === IMAGETYPE_PNG) {
          $files[$fid]['transparency'] = $this->detectTransparency($image_path);
        }
      }
    }

    /* Table exists in burdamagazinorg/thunder-project and needs also be updated. */
    if (!empty($files) && $this->db->schema()->tableExists('media__field_image')) {
      $connection = \Drupal::database();
      $mids = $connection->query("SELECT entity_id FROM media__field_image where field_image_target_id IN (:fids[])", [':fids[]' => array_keys($files)])->fetchAllAssoc('entity_id');

      $media_type_storage = $this->entityManager->getStorage('media');
      $media_images = $media_type_storage->loadMultiple(array_keys($mids));

      foreach ($media_images as $media) {
        /** @var \Drupal\media_entity\Entity\Media $media */
        if ($media->hasField('field_image')) {
          $media_field_image = $media->get('field_image')->getValue();
          $fid = $media_field_image[0]['target_id'];

          $files[$fid]['media'] = $media;
        }
      }
    }

    return $files;
  }

  /**
   * Resize given Images to the max width.
   *
   * @param array $images
   *   Images.
   * @param int $max_width
   *   Max width.
   *
   * @return array
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function resizeImages(array $images, $max_width) {
    $current_size = 0;
    $new_size = 0;
    $images_converted = 0;
    foreach ($images as $element) {
      /** @var \Drupal\file\Entity\File $file */
      $file = $element['file'];
      $path = $element['path'];
      $t = isset($element['transparency']) && $element['transparency'];

      $current_size += filesize($path);
      list($new_width, $new_heigth) = $this->resizeImageToWidth($max_width, $path, $t);
      $new_size += filesize($path);

      if (isset($element['media'])) {
        /** @var \Drupal\media_entity\Entity\Media $media */
        $media = $element['media'];
        $media_field_image = $media->get('field_image')->getValue();
        $media_field_image[0]['width'] = $new_width;
        $media_field_image[0]['height'] = $new_heigth;
        $media->set('field_image', $media_field_image);

        $thumbnail = $media->get('thumbnail')->getValue();
        $thumbnail[0]['width'] = $new_width;
        $thumbnail[0]['height'] = $new_heigth;
        $media->set('thumbnail', $thumbnail);
        $media->save();
      }

      $file->setSize(filesize($path));
      $file->save();

      $images_converted++;
    }

    $current_size = round($current_size / 1024 / 1024, 2);
    $new_size = round($new_size / 1024 / 1024, 2);

    return [$images_converted, $current_size, $new_size];
  }

  /**
   * Resize a Image to the given width.
   *
   * @param int $max_width
   *   Max width.
   * @param string $filename
   *   Path to the Image.
   * @param bool $transparency
   *   Image has Transparency?.
   *
   * @return array
   */
  private function resizeImageToWidth($max_width, $filename, $transparency) {
    list($width, $height, $type) = getimagesize($filename);
    $image = $this->loadImage($filename, $type);

    $ratio = $max_width / $width;
    $new_height = round($height * $ratio);

    $new_image = imagecreatetruecolor($max_width, $new_height);

    if ($type === IMAGETYPE_PNG && $transparency) {
      $color = imagecolorallocate($new_image, 255, 255, 255);
      imagefill($new_image, 0, 0, $color);
    }

    imagecopyresampled($new_image, $image, 0, 0, 0, 0, $max_width, $new_height, $width, $height);

    $this->saveImage($new_image, $filename, $type);

    clearstatcache(TRUE, $filename);

    return [$max_width, $new_height];
  }

  /**
   * Load Image by Type.
   *
   * @param string $filename
   *   Path to the Image.
   * @param int $type
   *   Image Type.
   *
   * @return resource|null
   */
  private function loadImage($filename, $type) {
    $image = NULL;

    switch ($type) {
      case IMAGETYPE_JPEG:
        $image = imagecreatefromjpeg($filename);
        break;

      case IMAGETYPE_PNG:
        $image = imagecreatefrompng($filename);
        break;
    }

    return $image;
  }

  /**
   * Save an Image by type.
   *
   * @param resource $image
   *   Image object.
   * @param string $filename
   *   Path where the Image should be stored.
   * @param int $type
   *   Image Type.
   * @param int $quality
   *   JPG Quality.
   */
  private function saveImage($image, $filename, $type, $quality = 100) {
    switch ($type) {
      case IMAGETYPE_JPEG:
        imagejpeg($image, $filename, $quality);
        break;

      case IMAGETYPE_PNG:
        imagepng($image, $filename);
        break;
    }

    imagedestroy($image);
  }

  /**
   * Convert PNG to JPG Image.
   *
   * @param string $original_file
   *   PNG File path.
   * @param string $output_file
   *   JPG File path.
   * @param int $quality
   *   Target JPG Quality.
   */
  private function gdPngToJpg($original_file, $output_file, $quality = 100) {
    $image = $this->loadImage($original_file, IMAGETYPE_PNG);
    $this->saveImage($image, $output_file, IMAGETYPE_JPEG, $quality);
  }

  /**
   * Imagick PNG to JPG conversion.
   *
   * @param string $original_file
   *   PNG File path.
   * @param string $output_file
   *   JPG File path.
   * @param int $quality
   *   Target JPG Quality.
   *
   * @throws \ImagickException
   */
  private function imagickPngToJpg($original_file, $output_file, $quality = 100) {
    $im = new \Imagick();
    $im->readImage($original_file);
    $im = $im->flattenImages();
    $im->setCompressionQuality($quality);
    $im->setImageFormat('jpg');
    $im->writeImages($output_file, FALSE);
    $im->clear();
  }

  /**
   * Detect if the Image has Transparency.
   *
   * From https://stackoverflow.com/questions/5495275/how-to-check-if-an-image-has-transparency-using-gd.
   *
   * @param string $file
   *   Image File Path.
   *
   * @return bool
   */
  private function detectTransparency($file) {
    if (!@getimagesize($file)) {
      return FALSE;
    }

    if (ord(file_get_contents($file, FALSE, NULL, 25, 1)) & 4) {
      return TRUE;
    }

    $content = file_get_contents($file);
    if (stripos($content, 'PLTE') !== FALSE && stripos($content, 'tRNS') !== FALSE) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Get Fid from File Object.
   *
   * @param \Drupal\file\Entity\FileInterface $file
   *   File.
   *
   * @return mixed
   */
  public function getFid(FileInterface $file) {
    $fid = $file->get('fid')->getValue();

    return $fid[0]['value'];
  }

}
