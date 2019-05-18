<?php

/**
 * @file
 * Contains \Drupal\system\Plugin\ImageToolkit\GDToolkit.
 */

namespace Drupal\picture\Plugin\ImageToolkit;

use Drupal\Component\Utility\Unicode;
use Drupal\system\Plugin\ImageToolkit\GDToolkit;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the GD2 toolkit for image manipulation within Drupal.
 *
 * @ImageToolkit(
 *   id = "gd_webp",
 *   title = @Translation("GD2 image manipulation toolkit with WebP support")
 * )
 */
class GDToolkitWebP extends GDToolkit {

  const IMAGETYPE_WEBP = 'WEBP';

  /**
   * {@inheritdoc}
   */
  protected function load() {
    // Return immediately if the image file is not valid.
    if (!$this->isValid()) {
      return FALSE;
    }
    switch ($this->getType()) {
      case GDToolkitWebP::IMAGETYPE_WEBP:
        $function = 'imagecreatefromwebp';
        break;

      default:
        $function = 'imagecreatefrom' . image_type_to_extension($this->getType(), FALSE);
    }

    if (function_exists($function) && $resource = $function($this->getImage()->getSource())) {
      $this->setResource($resource);
      if (imageistruecolor($resource)) {
        return TRUE;
      }
      else {
        // Convert indexed images to true color, so that filters work
        // correctly and don't result in unnecessary dither.
        $new_image = $this->createTmp($this->getType(), imagesx($resource), imagesy($resource));
        if ($ret = (bool) $new_image) {
          imagecopy($new_image, $resource, 0, 0, 0, 0, imagesx($resource), imagesy($resource));
          imagedestroy($resource);
          $this->setResource($new_image);
        }
        return $ret;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function save($destination) {
    $scheme = file_uri_scheme($destination);
    // Work around lack of stream wrapper support in imagejpeg() and imagepng().
    if ($scheme && file_stream_wrapper_valid_scheme($scheme)) {
      // If destination is not local, save image to temporary local file.
      $local_wrappers = file_get_stream_wrappers(STREAM_WRAPPERS_LOCAL);
      if (!isset($local_wrappers[$scheme])) {
        $permanent_destination = $destination;
        $destination = drupal_tempnam('temporary://', 'gd_');
      }
      // Convert stream wrapper URI to normal path.
      $destination = drupal_realpath($destination);
    }
    switch ($this->getType()) {
      case GDToolkitWebP::IMAGETYPE_WEBP:
        $function = 'imagewebp';
        break;

      default:
        $function = 'image' . image_type_to_extension($this->getType(), FALSE);
        break;
    }
    if (!function_exists($function)) {
      return FALSE;
    }
    if ($this->getType() == IMAGETYPE_JPEG) {
      $success = $function($this->getResource(), $destination, $this->configFactory->get('system.image.gd')->get('jpeg_quality'));
    }
    else {
      // Always save PNG images with full transparency.
      if ($this->getType() == IMAGETYPE_PNG) {
        imagealphablending($this->getResource(), FALSE);
        imagesavealpha($this->getResource(), TRUE);
      }
      $success = $function($this->getResource(), $destination);
    }
    // Move temporary local file to remote destination.
    if (isset($permanent_destination) && $success) {
      return (bool) file_unmanaged_move($destination, $permanent_destination, FILE_EXISTS_REPLACE);
    }
    return $success;
  }

  /**
   * {@inheritdoc}
   */
  public function parseFile() {
    $data = @getimagesize($this->getImage()->getSource());
    if ($data && in_array($data[2], static::supportedTypes())) {
      $this->setType($data[2]);
      $this->preLoadInfo = $data;
      return TRUE;
    }
    else {
      // Determine if this is a WebP image.
      $handle = fopen($this->getImage()->getSource(), 'rb');
      $header = fread($handle, 12);
      if (Unicode::substr($header, 0, 4) === 'RIFF' && Unicode::substr($header, -4) === 'WEBP') {
        switch (fread($handle, 4)) {
          case 'VP8 ':
            fseek($handle, 26);
            $dimensions = fread($handle, 4);
            $width = unpack('V', $dimensions);
            $this->preLoadInfo[0] = $width[1] & 0x3fff;
            $this->preLoadInfo[1] = ($width[1] >> 16) & 0x3fff;
            break;

          case 'VP8L':
            fseek($handle, 21);
            $dimensions = fread($handle, 4);
            $width = unpack('V', $dimensions);
            $this->preLoadInfo[0] = ($width[1] & 0x3fff) + 1;
            $this->preLoadInfo[1] = (($width[1] >> 14) & 0x3fff) + 1;
            break;
        }
        $this->setType(GDToolkitWebP::IMAGETYPE_WEBP);
        $this->preLoadInfo[2] = GDToolkitWebP::IMAGETYPE_WEBP;
        $this->preLoadInfo[3] = 'height="' . $this->preLoadInfo[1] . '" width="' . $this->preLoadInfo[1] . '"';
        $this->preLoadInfo['mime'] = 'image/webp';
        // The 'channels' and 'bits' elements are not required, so omit them.
        fclose($handle);
        return TRUE;
      }
      fclose($handle);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getMimeType() {
    switch ($this->getType()) {
      case GDToolkitWebP::IMAGETYPE_WEBP:
        return 'image/webp';

      default:
        return $this->getType() ? image_type_to_mime_type($this->getType()) : '';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getRequirements() {
    $requirements = array();

    $info = gd_info();
    $requirements['version'] = array(
      'title' => t('GD library'),
      'value' => $info['GD Version'],
    );

    // Check for filter and rotate support.
    if (!function_exists('imagefilter') || !function_exists('imagerotate')) {
      $requirements['version']['severity'] = REQUIREMENT_WARNING;
      $requirements['version']['description'] = t('The GD Library for PHP is enabled, but was compiled without support for functions used by the rotate and desaturate effects. It was probably compiled using the official GD libraries from http://www.libgd.org instead of the GD library bundled with PHP. You should recompile PHP --with-gd using the bundled GD library. See <a href="@url">the PHP manual</a>.', array('@url' => 'http://www.php.net/manual/book.image.php'));
    }
    elseif (version_compare(PHP_VERSION, '5.5.0') < 0) {
      $requirements['version']['severity'] = REQUIREMENT_ERROR;
      $requirements['version']['description'] = t('You need at least PHP version 5.5.0 to support WebP with the GD library. Your current PHP version is @version', array('@version' => PHP_VERSION));
    }

    return $requirements;
  }

  /**
   * {@inheritdoc}
   */
  public static function isAvailable() {
    // GD2 support is available.
    return version_compare(PHP_VERSION, '5.5.0') > 0 && function_exists('imagegd2');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSupportedExtensions() {
    $extensions = array();
    foreach (static::supportedTypes() as $image_type) {
      if ($image_type == GDToolkitWebP::IMAGETYPE_WEBP) {
        $extensions[] = 'webp';
      }
      else {
        $extensions[] = Unicode::strtolower(image_type_to_extension($image_type, FALSE));
      }
    }
    return $extensions;
  }

  /**
   * {@inheritdoc}
   */
  public function extensionToImageType($extension) {
    if ($extension === 'webp') {
      return GDToolkitWebP::IMAGETYPE_WEBP;
    }
    foreach ($this->supportedTypes() as $type) {
      if (image_type_to_extension($type, FALSE) === $extension) {
        return $type;
      }
    }
    return IMAGETYPE_UNKNOWN;
  }

  /**
   * {@inheritdoc}
   */
  protected static function supportedTypes() {
    return array(IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_GIF, GDToolkitWebP::IMAGETYPE_WEBP);
  }

}
