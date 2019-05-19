<?php

namespace Drupal\views_restricted\Form\Ajax;

use Drupal\views\ViewEntityInterface;
use Drupal\views_restricted\Traits\MassageResponseTrait;
use Drupal\views_restricted\ViewsRestrictedHelper;
use Drupal\views_restricted\ViewsRestrictedInterface;

class Rearrange extends \Drupal\views_ui\Form\Ajax\Rearrange {

  use MassageResponseTrait;

  public function getForm(ViewEntityInterface $view, $display_id, $js, $type = NULL, ViewsRestrictedInterface $views_restricted = NULL) {
    ViewsRestrictedHelper::setViewsRestricted($view, $views_restricted);
    $response = parent::getForm($view, $display_id, $js, $type);
    $this->massageResponse($response, $views_restricted, $view, $display_id, $js, $type);
    return $response;
  }

}
