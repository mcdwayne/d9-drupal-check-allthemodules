<?php

namespace Drupal\views_restricted\Form\Ajax;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\ViewEntityInterface;
use Drupal\views_restricted\Traits\MassageResponseTrait;
use Drupal\views_restricted\ViewsRestrictedHelper;
use Drupal\views_restricted\ViewsRestrictedInterface;

class AddHandler extends \Drupal\views_ui\Form\Ajax\AddHandler {

  use MassageResponseTrait;

  public function getForm(ViewEntityInterface $view, $display_id, $js, $type = NULL, ViewsRestrictedInterface $views_restricted = NULL) {
    ViewsRestrictedHelper::setViewsRestricted($view, $views_restricted);
    $response = parent::getForm($view, $display_id, $js, $type);
    $this->massageResponse($response, $views_restricted, $view, $display_id, $js, $type);
    return $response;
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $build = parent::buildForm($form, $form_state);
    $view = $form_state->get('view');
    if ($viewsRestricted = ViewsRestrictedHelper::extractViewsRestricted($view)) {
      $display_id = $form_state->get('display_id');
      $type = $form_state->get('type');

      foreach ($build['options']['name']['#options'] as $key => &$option) {
        list($table, $fieldAndAlias) = explode('.', $key, 2);
        list($field, $alias) = explode('$', $fieldAndAlias, 2) + [1 => ''];
        $accessResult = $viewsRestricted->access($view, $display_id, $type, $table, $field, $alias);
        if (!$accessResult->isAllowed()) {
          // #access is not enough here.
          unset($build['options']['name']['#options'][$key]);
        }
      }
    }
    return $build;
  }

}
