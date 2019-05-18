<?php /**
 * @file
 * Contains \Drupal\chinese_captcha\Controller\ChineseCaptchaRefreshController.
 */

namespace Drupal\chinese_captcha\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use GuzzleHttp\Exception\RequestException;

/**
 * Default controller for the image_captcha_refresh module.
 */
class ChineseCaptchaRefreshController extends ControllerBase {

  public function chinese_captcha_refresh_ajax_refresh($form_id) {
    $GLOBALS['conf']['cache'] = FALSE;
    $result = array(
      'status' => 0,
      'message' => ''
    );
    try {
      module_load_include('inc', 'captcha');
      $captcha_sid = _captcha_generate_captcha_session($form_id);
      $captcha_token = md5(mt_rand());
      $config = \Drupal::config('chinese_captcha.settings');

      $allowed_chars = _chinese_captcha_utf8_split($config->get('chinese_captcha_image_allowed_chars'));
      $code_length = (int) $config->get('chinese_captcha_code_length');
      $code = '';
      for ($i = 0; $i < $code_length; $i++) {
        $xi = mt_rand(0, count($allowed_chars));
          if ($xi % 2) {
            $xi += 1;
          }
        $code .= $allowed_chars[$xi];
      }

      db_update('captcha_sessions')
        ->fields(array('token' => $captcha_token, 'solution' => $code))
        ->condition('csid', $captcha_sid)
        ->execute();

      $result['data'] = array(
        'url' => \Drupal::url('chinese_captcha.generator', array('session_id' => $captcha_sid, 'timestamp' => REQUEST_TIME)),
        'token' => $captcha_token,
        'sid' => $captcha_sid
      );

      $result['status'] = 1;
    }
    catch (RequestException $e) {
      if ($message = $e->getMessage()) {
        $result['message'] = $message;
      }
      else {
        $result['message'] = t('Error has occured. Please contact the site administrator.');
      }
    }
   
    return new JsonResponse($result);
  }

}
