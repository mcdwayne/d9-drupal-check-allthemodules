<?php

namespace Drupal\hipa\Controller;

use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller routines for hipa module.
 */
class HipaController extends ControllerBase {

  /**
   * Returns code.
   *
   * @param int $fid
   *   FID of file.
   * @param string $image_style
   *   Image style for picture.
   *
   * @return string
   *   Hash code.
   */
  public static function generateCode($fid, $image_style) {
    $file = File::load($fid);
    if ($file && $image_style) {
      $config = \Drupal::config('hipa.settings');
      $hipa_salt = $config->get('hipa.hipa_salt');
      $uri = $file->getFileUri();
      if ($image_style == 'default') {
        $url = $url = file_create_url($uri);
      }
      else {
        $imagestyle = ImageStyle::load($image_style);
        $url = $imagestyle->buildUrl($uri);
      }
      $timestamp = $file->getCreatedTime();
      $code = sha1($url . $hipa_salt . $timestamp);
      return $code;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Renders an image.
   *
   * @param int $fid
   *   FID of file.
   * @param string $image_style
   *   Image style for picture.
   * @param string $code
   *   Validate code.
   */
  public function generatePicture($fid, $image_style, $code) {
    $file = File::load($fid);
    if ($file && $image_style) {
      $new_code = $this->generateCode($fid, $image_style);
      $uri = $file->getFileUri();
      if (($code === $new_code) && !empty($_SERVER['HTTP_REFERER'])) {
        if ($image_style == 'default') {
          $url = file_create_url($uri);
        }
        else {
          $imagestyle = ImageStyle::load($image_style);
          $url = $imagestyle->buildUrl($uri);
        }
        $fp = fopen($url, "rb");
        if ($fp) {
          header("Content-type: " . $file->getMimeType());
          fpassthru($fp);
          exit();
        }
        throw new NotFoundHttpException();
      }
      else {
        throw new NotFoundHttpException();
      }
    }
    else {
      throw new NotFoundHttpException();
    }
  }

}
