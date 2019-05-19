<?php

namespace Drupal\views_restricted\Form\Ajax;

use Drupal\views\ViewEntityInterface;
use Drupal\views_restricted\Traits\MassageResponseTrait;
use Drupal\views_restricted\ViewsRestrictedHelper;
use Drupal\views_restricted\ViewsRestrictedInterface;

class ReorderDisplays extends \Drupal\views_ui\Form\Ajax\ReorderDisplays {

  use MassageResponseTrait;

  public function getForm(ViewEntityInterface $view, $display_id, $js, ViewsRestrictedInterface $views_restricted = NULL) {
    ViewsRestrictedHelper::setViewsRestricted($view, $views_restricted);
    $response = parent::getForm($view, $display_id, $js);
    $this->massageResponse($response, $views_restricted, $view, $display_id, $js);
    return $response;
  }

}
