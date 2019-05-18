<?php

namespace Drupal\group_purl\Plugin\Purl\Method;

use Drupal\purl\Plugin\Purl\Method\PathPrefixMethod;
use Symfony\Component\HttpFoundation\Request;

/**
 * @PurlMethod(
 *   id="group_prefix",
 *   title = @Translation("Group Content."),
 *   stages={
 *      Drupal\purl\Plugin\Purl\Method\MethodInterface::STAGE_PROCESS_OUTBOUND,
 *      Drupal\purl\Plugin\Purl\Method\MethodInterface::STAGE_PRE_GENERATE
 *   }
 * )
 */
class GroupPrefixMethod extends PathPrefixMethod {

  /**
   *
   */
  public function contains(Request $request, $modifier) {
    $uri = $request->getRequestUri();
    if ($uri === '/' . $modifier) {
      return FALSE;
    }
    return $this->checkPath($modifier, $uri);
  }

  protected function checkPath($modifier, $uri) {
    if ($uri === '/' . $modifier) {
      return FALSE;
    }
    return strpos($uri, '/' . $modifier . '/') === 0;

  }

  /**
   *
   */
  public function alterRequest(Request $request, $identifier) {
    // cannot use $request->uri as this sets it to the current server URI, making
    // it too late to modify
    $uri = $request->server->get('REQUEST_URI');
    $newPath = substr($uri, strlen($identifier) + 1);
    $request->server->set('REQUEST_URI', $newPath);
  }

  /**
   *
   */
  public function enterContext($modifier, $path, array &$options) {
    if (isset($options['purl_exit']) && $options['purl_exit']) {
      return $path;
    }
    return '/' . $modifier . $path;
  }

  /**
   *
   */
  public function exitContext($modifier, $path, array &$options) {
    if (!$this->checkPath($modifier, $path)) {
      return NULL;
    }

    return substr($path, 0, strlen($modifier) + 1);
  }

}
