<?php

/**
 * @file
 * Contains CAPTCHA image response class.
 */

namespace Drupal\chinese_captcha\Response;

use Drupal\Core\Logger\LoggerChannelInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Config\Config;

/**
 * Response which is returned as the captcha for chinese_captcha.
 *
 * @package Drupal\chinese_captcha\Response
 */
class ChineseCaptchaResponse extends Response {

  /**
   * Image Captcha config storage.
   *
   * @var Config
   */
  protected $config;

  /**
   * Watchdog logger channel for captcha.
   *
   * @var LoggerChannelInterface
   */
  protected $logger;

  /**
   * Recourse with generated image.
   *
   * @var resource
   */
  protected $image;

  /**
   * {@inheritdoc}
   */
  public function __construct(Config $config, LoggerChannelInterface $logger, $callback = NULL, $status = 200, $headers = []) {
    parent::__construct(NULL, $status, $headers);

    $this->config = $config;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(Request $request) {
    $session_id = $request->get('session_id');

    $code = db_query("SELECT solution FROM {captcha_sessions} WHERE csid = :csid",
      [':csid' => $session_id]
    )->fetchField();

    if ($code !== FALSE) {
      $this->image = @$this->generateImage($code);

      if (!$this->image) {
        $this->logger->log(WATCHDOG_ERROR, 'Generation of chinese CAPTCHA failed.', []);
      }
    }

    return parent::prepare($request);
  }

  /**
   * Base function for generating a chinese CAPTCHA.
   *
   * @param string $code
   *   String code to be presented on chinese.
   *
   * @return resource
   *   Image to be outputted contained $code string.
   */
  protected function generateImage($code) {
    $w = 150;
    $h = 50;
    //font file path
    $fontface = drupal_get_path('module', 'chinese_captcha') . '/font/fzcyjt.ttf';
    
    $code = iconv('utf-8', 'GB2312', $code);
    $im = imagecreatetruecolor($w, $h);
    $bkcolor = imagecolorallocate($im, 250, 250, 250);
    imagefill($im, 0, 0, $bkcolor);

    /***Add interference***/
    for ($i = 0; $i < 15; $i++) {
      $fontcolor = imagecolorallocate($im, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
      imagearc($im, mt_rand(-10, $w), mt_rand(-10, $h), mt_rand(30, 300), mt_rand(20, 200), 55, 44, $fontcolor);
    }

    for ($i = 0; $i < 255; $i++) {
      $fontcolor = imagecolorallocate($im, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
      imagesetpixel($im, mt_rand(0, $w), mt_rand(0, $h), $fontcolor);
    }

    /***Image content***/
    for ($i = 0; $i < 4; $i++) {
      $fontcolor = imagecolorallocate($im, mt_rand(0, 120), mt_rand(0, 120), mt_rand(0, 120));
      $codex = iconv("GB2312", "UTF-8", substr($code, $i * 2, 2));
      imagettftext($im, mt_rand(14, 18), mt_rand(-60, 60), 30 * $i + 20, mt_rand(30, 35), $fontcolor, $fontface, $codex);
    }

    imagepng($im);
    imagedestroy($im);
  }

}
