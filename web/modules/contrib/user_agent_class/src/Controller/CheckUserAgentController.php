<?php

namespace Drupal\user_agent_class\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Utility\Xss;

/**
 * Class CheckUserAgentController.
 */
class CheckUserAgentController extends ControllerBase {

  /**
   * CheckUserAgent.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return array
   *   Return User-Agent string.
   */
  public function checkUserAgent(Request $request) {
    $result = Xss::filter($request->headers->get('user-agent'));
    return [
      '#type' => 'markup',
      '#markup' => '<b>Current User-Agent:</b> <i>' . $result . '</i>',
    ];
  }

}
