<?php
/**
 * Created by PhpStorm.
 * User: john
 * Date: 10/30/18
 * Time: 9:38 PM
 */

namespace Drupal\group_purl\Plugin\Purl\Method;

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Site\Settings;
use Drupal\purl\Plugin\Purl\Method\MethodAbstract;
use Drupal\purl\Plugin\Purl\Method\OutboundRouteAlteringInterface;
use Drupal\purl\Plugin\Purl\Method\RequestAlteringInterface;
use Drupal\purl\Plugin\Purl\Method\SubdomainMethod;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;


/**
 * @PurlMethod(
 *   id="group_subdomain",
 *   title = @Translation("Group Subdomain."),
 *   stages={
 *      Drupal\purl\Plugin\Purl\Method\MethodInterface::STAGE_PROCESS_OUTBOUND
 *   }
 * )
 */
class GroupSubdomain extends MethodAbstract implements OutboundRouteAlteringInterface, ContainerAwareInterface, RequestAlteringInterface {

  use ContainerAwareTrait;

  public function contains(Request $request, $modifier)
  {
    $baseHost = $this->getBaseHost();

    if (!$baseHost) {
      return false;
    }

    $host = $request->getHost();

    if ($host === $this->getBaseHost()) {
      return false;
    }

    return $this->hostContainsModifier($modifier, $request->getHost());
  }

  protected function hostContainsModifier($modifier, $host)
  {
    return strpos($host, $modifier . '.') === 0;
  }

  protected function getBaseHost()
  {
    // Retrieve this from request context.
    return Settings::get('purl_base_domain');
  }

  protected function getRequestContext()
  {
    return $this->container->get('router.request_context');
  }


  public function alterOutboundRoute($routeName, $modifier, Route $route, array &$parameters, BubbleableMetadata $metadata = NULL) {
    // TODO: Implement alterOutboundRoute() method.
    if ($modifier) {

    }
  }

  public function checkPath($modifier, $uri) {
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
    if (strpos($uri, '/' . $identifier) === 0) {
      return FALSE;
    };
    if ($uri == '/') {
      $newPath = '/' . $identifier;
      $request->server->set('REQUEST_URI', $newPath);
      return TRUE;
    }
    return FALSE;
  }

  /**
   *
   */
  public function enterContext($modifier, $path, array &$options) {
    // first fix up path...
    if (isset($options['host'])) {
      $host = $options['host'];
    } else {
      $host = $this->getRequestContext()->getHost();
    }
    // Next, bail under certain circumstances
    if (isset($options['purl_exit']) && $options['purl_exit']) {
      $options['host'] = $this->getBaseHost();
      $options['absolute'] = TRUE;
      return $path;
    }
    if (isset($options['route'])) {
      if (!empty($options['route']->getOptions()['_admin_route'])) {
        return null;
      }
    }
    // finally, check path and insert group prefix for next request
    return $path;
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