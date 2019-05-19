<?php

namespace Drupal\views_restricted\Traits;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Url;
use Drupal\views\ViewEntityInterface;
use Drupal\views_restricted\ViewsRestrictedInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

trait MassageResponseTrait {

  public function massageResponse($response, ViewsRestrictedInterface $views_restricted, ViewEntityInterface $view, $display_id, $js, $type = NULL, $id = NULL) {
    // Fix the redirect URLs from ViewsFormBase::getForm
    if ($response instanceof RedirectResponse) {
      // L146 / L154
      $targetUrl = $response->getTargetUrl();
      $this->fixUrl($targetUrl, $views_restricted);
      $response->setTargetUrl($targetUrl);
    }
    if ($response instanceof AjaxResponse) {
      // L129 / L149>171 / L163>171
      foreach ($response->getCommands() as &$command) {
        if (is_array($command) && $command['command'] === 'viewsSetForm') {
          $this->fixUrl($command['url'], $views_restricted);
        }
      }
    }
    return $response;
  }

  /**
   * @param $targetUrl
   * @param \Drupal\views_restricted\ViewsRestrictedInterface $views_restricted
   */
  private function fixUrl(&$targetUrl, ViewsRestrictedInterface $views_restricted) {
    $request = Request::create($targetUrl);
    $url = Url::createFromRequest($request);
    if (array_key_exists('views_restricted', $url->getRouteParameters())) {
      $url->setRouteParameter('views_restricted', $views_restricted->getPluginId());
      $targetUrl = $url->toString();
    }
  }

}
