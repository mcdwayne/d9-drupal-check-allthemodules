<?php

namespace Drupal\shrinktheweb\Controller;

use Drupal\Core\Controller\ControllerBase;
use \Drupal\Core\Render\HtmlResponse;

/**
 * Class ShrinkTheWebCallbackController.
 *
 * @package Drupal\shrinktheweb\Controller
 */
class ShrinkTheWebCallbackController extends ControllerBase {

  public function receiveScreenshot() {

    // Newer PHP versions will throw an error if this is not set,
    // here just in case php.ini doesn't do it.
    date_default_timezone_set('UTC');
    module_load_include('inc', 'shrinktheweb', 'shrinktheweb.api');

    $status = 'Invalid Token';

    if ($_GET['token'] == TOKEN) {
      $contents = json_decode(file_get_contents("php://input"));

      // Assign the original arguments.
      $aArgs['stwfull'] = $contents->full;
      $aArgs['stwxmax'] = $contents->xmax;

      // For backward compatibility, we omit ymax in hash calc, if not full.
      if ($contents->full) {
        $aArgs['stwymax'] = $contents->ymax;
      }
      $aArgs['stwnrx'] = $contents->nrx;
      $aArgs['stwnry'] = $contents->nry;
      $aArgs['stwq'] = $contents->quality;
      $aArgs['stwurl'] = urldecode($contents->origurl);

      $sHash = shrinktheweb_generateHash($aArgs);

      if ($contents->exists == 'true') {

        $sFilename = $sHash . '.jpg';

        $sFile = THUMBNAIL_DIR . $sFilename;

        // Create cache directory if it doesn't exist.
        shrinktheweb_createCacheDirectory();

        // Do we have image payload?
        if ($contents->data) {
          $sRemoteData = base64_decode($contents->data);

          $rFile = fopen($sFile, "w+");
          fputs($rFile, $sRemoteData);
          fclose($rFile);
        }
        else {
          // If notifyNoPush, we are given the API URL to download
          // (adds a call but saves bandwidth per image).
          $sRemoteUrl = $contents->downloadurl . '&stwaccesskeyid=' . ACCESS_KEY . '&stwu=' . SECRET_KEY;

          // Save to disk.
          shrinktheweb_downloadRemoteImageToLocalPath($sRemoteUrl, $sFile);
        }
      }
      // Add the request to the database if debug is enabled.
      if (DEBUG) {
        $aFields = array(
          'stw_domain' => urldecode($contents->origurl),
          'stw_timestamp' => time(),
          'stw_capturedon' => $contents->responsetimestamp,
          'stw_quality' => property_exists($contents, 'quality') ? $contents->quality : 95,
          'stw_full' => property_exists($contents, 'full') ? $contents->full : 0,
          'stw_xmax' => property_exists($contents, 'xmax') ? $contents->xmax : 0,
          'stw_ymax' => property_exists($contents, 'ymax') ? $contents->ymax : 0,
          'stw_nrx' => property_exists($contents, 'nrx') ? $contents->nrx : 1024,
          'stw_nry' => property_exists($contents, 'nry') ? $contents->nry : 768,
          'stw_invalid' => ($contents->exists == 'false') ? 1 : 0,
          'stw_stwerrcode' => property_exists($contents, 'responsecode') ? $contents->responsecode : 0,
          'stw_error' => ($contents->verified == 'false') ? 1 : 0,
          'stw_errcode' => $contents->responsestatus,
          'stw_hash' => $sHash,
        );
        \Drupal::database()->merge('shrinktheweb_log')
          ->key(array('stw_hash' => $sHash))
          ->fields($aFields)
          ->updateFields($aFields)
          ->execute();
      }
      entity_render_cache_clear();
      $status = 'Success';
    }

    $response = new HtmlResponse();
    $response->setContent($status);
    return $response;
  }
}
