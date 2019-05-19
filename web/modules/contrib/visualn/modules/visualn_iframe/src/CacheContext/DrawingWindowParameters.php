<?php

namespace Drupal\visualn_iframe\CacheContext;

use Drupal\Core\Cache\Context\CacheContextInterface;
use Drupal\Core\Cache\Context\RequestStackCacheContextBase;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\visualn\WindowParametersInterface;
use Drupal\visualn\WindowParametersTrait;


// @see \Drupal\Core\Cache\Context\QueryArgsCacheContext for example
class DrawingWindowParameters extends RequestStackCacheContextBase implements CacheContextInterface, WindowParametersInterface {

  // add trait to use cleanWindowParameters() method
  use WindowParametersTrait;

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('VisualN IFrame Drawing Window Parameters');
  }

  /**
  * {@inheritdoc}
  */
  public function getContext() {

    $window_parameters = [];

    if ($this->requestStack->getCurrentRequest()->query->has('width')) {
      $window_parameters['width'] = $this->requestStack->getCurrentRequest()->query->get('width');
    }
    if ($this->requestStack->getCurrentRequest()->query->has('height')) {
      $window_parameters['height'] = $this->requestStack->getCurrentRequest()->query->get('height');
    }

    $this->setWindowParameters($window_parameters);
    $this->cleanWindowParameters();

    // @todo: Get allowed width and height ranges to avoid cache overflow
    // @todo: Optionally add width and height to the iframe url in sharing box
    // @todo: Store ranges info for each single visualn_iframe entity
    //   in an entity field, different types of ranges and settings
    //   may be implemented as a plugin type later.
    //   As an option, iframe can fallback to defaults set on iframe
    //   settings page. Global setting may rely on plugins too.
    //   The allowed ranges settings for a shared drawing iframe could be
    //   set using drawing properties form (and block configuration form).
    // @todo: It needs a way to load visualn_iframe entity to get allowed ranges
    //   settings for the given iframe entity. Maybe get it by hash from
    //   request parameters or alternatively use an optional parameter
    //   and implement CalculatedCacheContextInterface though iframe content
    //   providers are not supposed to know anything about iframe entities.

    // @todo: Load visualn_iframe entity by hash and try to get allowed
    //   ranges settings, otherwise fallback to defaults from iframe
    //   settings page (needs to be implemented), currently use the
    //   temporary solution below instead.
    // $hash = \Drupal::routeMatch()->getParameter('hash');
    // $visualn_iframe = VisualNIFrame::getIFrameEntityByHash($hash);

    // @note: large values of allowed ranges could be undesirable for
    //   performance considerations, e.g. large images generation etc.

    // @todo: Strictly speaking, it is not correct to use window_parameters methods
    //   for iframe_parameters since the latter can have different format and structure
    //   and should be translated into window parameters if needed,
    //   though now for brevity and simplicity consider them have the same format.
    //   While window_parameters are supposed to be used when creating drawing build
    //   by drawers or fetchers, iframe_parameters may be processed in a different
    //   way and not necessarily translated into window_parameters and passed down
    //   the line, though currently it is the only use for them. But should be distinguished
    //   anyway.
    $width = 'default';
    $height = 'default';
    // @todo: temporary solution
    foreach (['width', 'height'] as $parameter) {
      if (!empty($this->window_parameters[$parameter])) {
        $$parameter = $this->window_parameters[$parameter];
        $$parameter = min(10000, $$parameter);
        $step = 50;
        if ($$parameter >= $step) {
          $$parameter = floor(($$parameter)/$step)*$step;
        }
        else {
          $$parameter = $step;
        }
        // Override parameters to comply with allowed values being cached
        $this->requestStack->getCurrentRequest()->query->set($parameter, $$parameter);
      }
    }

    $parameters_str = "width:{$width}:height:{$height}";

    return $parameters_str;
  }

  /**
  * {@inheritdoc}
  */
  public function getCacheableMetadata() {
    return new CacheableMetadata();
  }

}
