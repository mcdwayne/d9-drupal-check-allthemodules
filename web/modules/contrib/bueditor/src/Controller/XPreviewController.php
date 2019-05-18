<?php

namespace Drupal\bueditor\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Controller\ControllerBase;


/**
 * Controller class for ajax preview path.
 */
class XPreviewController extends ControllerBase {

  /**
   * Handles ajax preview requests.
   */
  public function response(Request $request) {
    $user = $this->currentUser();
    // Check security token for authenticated users.
    if (!$user->isAnonymous()) {
      $token = $request->query->get('token');
      if (!$token || !\Drupal::csrfToken()->validate($token, 'xpreview')) {
        return new JsonResponse(['output' => $this->t('Invalid security token.'), 'status' => FALSE]);
      }
    }
    // Build output
    $data = ['output' => '', 'status' => TRUE];
    // Check input
    $input = $request->request->get('input');
    if (is_string($input) && ($input = trim($input)) !== '') {
      $used_format = filter_fallback_format();
      // Check format
      $format = $request->request->get('format');
      if ($format && is_string($format) && $format !== $used_format) {
        if ($format = \Drupal::entityTypeManager()->getStorage('filter_format')->load($format)) {
          if ($format->access('use', $user)) {
            $used_format = $format->id();
          }
        }
      }
      $data['usedFormat'] = $used_format;
      $build = ['#type' => 'processed_text', '#text' => $input, '#format' => $used_format];
      $data['output'] = '' . \Drupal::service('renderer')->renderPlain($build);
    }
    return new JsonResponse($data);
  }

}
