<?php

namespace Drupal\views_restricted;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\ViewEntityInterface;
use \Drupal\views_ui\ViewPreviewForm as ViewPreviewFormLegacy;

class ViewPreviewForm extends ViewPreviewFormLegacy {

  public function form(array $form, FormStateInterface $form_state) {
    $build = parent::form($form, $form_state);
    // Parent disables caching, so fortunately we can neglect that here.
    $view = ViewsRestrictedHelper::extractViewsUi($form_state);
    $display_id = $this->displayID;
    if ($viewsRestricted = ViewsRestrictedHelper::extractViewsRestricted($view)) {
      $accessResult = $viewsRestricted->access($view, $form_state->get('display_id'), 'preview');
      ViewsRestrictedHelper::removeBuildIfNoAccess($build, $accessResult);
      self::massagePreview($build, $viewsRestricted, $view, $display_id);
    }
    else {
      throw new \LogicException(sprintf('Could not find required views restricted plugin.'));
    }
    return $build;
  }

  protected function actions(array $form, FormStateInterface $form_state) {
    $build = parent::actions($form, $form_state);
    $view = ViewsRestrictedHelper::extractViewsUi($form_state);
    $display_id = $this->displayID;
    if ($viewsRestricted = ViewsRestrictedHelper::extractViewsRestricted($view)) {
      self::massagePreview($build, $viewsRestricted, $view, $display_id);
    }
    return $build;
  }


  private static function massagePreview(array &$build, ViewsRestrictedInterface $viewsRestricted, ViewEntityInterface $view, $display_id) {
    // Filter actions submit buttons.
    $type = 'preview';
    $accessResult = $viewsRestricted->access($view, $display_id, $type);
    if (!$accessResult->isAllowed()) {
      $build = [];
    }
  }

}
