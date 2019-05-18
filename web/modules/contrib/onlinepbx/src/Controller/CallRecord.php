<?php

namespace Drupal\onlinepbx\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller routines for page example routes.
 */
class CallRecord extends ControllerBase {

  /**
   * ONline.
   */
  public function record($uuid) {
    if ($mp3 = self::getCached($uuid)) {
      // Ajax-JSON data.
      if (\Drupal::request()->request->get('_ajax')) {
        $src = "<source src='/onlinepbx/record/$uuid/rec.mp3' type='audio/mpeg'>";
        // (autoplay|preload='(auto|none|metadata)')
        $play = "preload='metadata'";
        $audio = "<audio id='rec-$uuid' $play controls >$src</audio>";
        $responce = [
          'success' => TRUE,
          'audio' => $audio,
        ];
        return new JsonResponse($responce);
      }
      // Mp3 file Response.
      else {
        $file = @file_get_contents($mp3, TRUE);
        $filename = "rec-$uuid.mp3";
        $response = new Response($file);
        $response->headers->set('Content-Type', "audio/mpeg");
        $response->headers->set('Content-Disposition', "inline; filename=$filename");
        $response->headers->set('Content-length', strlen($file));
        $response->headers->set('X-Accel-Buffering', "no");
        $response->headers->set('Accept-Ranges', 'bytes');
        return $response;
      }
    }
    return new JsonResponse(['error' => '&nbsp; &nbsp;not found']);
  }

  /**
   * Get cached Record.
   */
  public static function getRecord($uuid) {
    $result = FALSE;
    $data = [
      "uuid" => $uuid,
      "download" => 1,
    ];
    $request = Api::request("history/search.json", $data);
    if ($mp3 = Api::isOk($request)) {
      $result = $mp3;
    }
    return $result;
  }

  /**
   * Get cached Record.
   */
  public static function getCached($uuid) {
    $data = &drupal_static("CallRecord::getCached($uuid)");
    if (!isset($data)) {
      $cache_key = "onlinepbx:rec:$uuid";
      if ($cache = \Drupal::cache()->get($cache_key)) {
        $data = $cache->data;
      }
      elseif ($data = self::getRecord($uuid)) {
        \Drupal::cache()->set($cache_key, $data, REQUEST_TIME + 15);
      }
    }
    return $data;
  }

}
