<?php

namespace Drupal\views_evi\Plugin\views_evi;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Html;
use Drupal\views_evi\Plugin\views\display_extender\ViewsEviDisplayExtender;
use Drupal\views_evi\ViewsEviHandlerTokenInterface;

abstract class ViewsEviHandlerTokenBase extends ViewsEviHandlerBase implements ViewsEviHandlerTokenInterface {

  /**
   * {@inheritdoc}
   */
  public function settingsForm($settings, &$form) {
    // Add our tokens to the whole form.
    $tokens = $this->getTokenReplacements(TRUE);
    $items = array();
    foreach($tokens as $token => $description) {
      $item = new FormattableMarkup('@k: @v', ['@k' => $token, '@v' => $description]);
      $items[] = $item;
    }

    $form['views_evi']['tokens'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Replacement tokens'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#weight' => 99,
    );
    $form['views_evi']['tokens']['value'] = array(
      '#theme' => 'item_list',
      '#items' => $items,
    );
    return array();
  }

  /**
   * Get token replacements.
   *
   * With code from @see \views_handler_field::get_render_tokens()
   */
  function getTokenReplacements($ui = FALSE) {

    $our_filter_wrapper = $this->getFilterWrapper();
    $evi = $our_filter_wrapper->getEvi();
    $display_handler = $evi->displayHandler;
    $view = $display_handler->view;

    if ($cached = $evi->getViewsEviCache('ViewsEviValueToken', "replacements-$ui")) {
      return $cached;
    }

    if (!isset($display_handler->getExtenders()['views_evi'])) {
      return array();
    }
    /** @var ViewsEviDisplayExtender $views_evi */
    $views_evi = $display_handler->getExtenders()['views_evi'];

    $replacements = array();

    // Argument tokens might have been set in view::_build_arguments()
    if (!empty($view->build_info['substitutions'])) {
      $replacements = $view->build_info['substitutions'];
    }

    // As a fallback we do set argument tokens.
    $count = 0;
    $argument_handlers = $view->display_handler->getHandlers('argument');
    $arg_replacements = array();
    foreach ($argument_handlers as $arg => $handler) {
      $count += 1;
      $t_args = array('@count' => $count);
      if (isset($arg_replacements["%$count"])) {
        $arg_replacements["%count"] = $ui ? $this->t('Title of argument @count', $t_args) : '';
      }
      // Use strip tags as there should never be HTML in the path. However, we need to preserve special characters like " that were removed by check_plain().
      $arg_replacements["!$count"] = $ui ?
        t('Value of argument @count', $t_args) :
        (isset($view->args[$count - 1]) ? strip_tags(Html::decodeEntities($view->args[$count - 1])) : '');
    }
    // Reverse so !10 is before !1
    $replacements += $arg_replacements;

    // Go through each filter and add options.
    foreach ($evi->getViewsEviFilterWrappers() as $filter_wrapper) {
      $identifier = $filter_wrapper->getIdentifier();
      $replacements["[form:$identifier]"] = $ui ?
        // Label is already sanitized.
        $this->t('Exposed form value for @label', array('@label' => $filter_wrapper->getEviLabel())) :
        @$_GET[$identifier];
    }

    $context = array(
      'view' => $view,
      'ui' => $ui,
    );
    \Drupal::moduleHandler()->alter('views_evi_tokens', $replacements, $context);

    $evi->setViewsEviCache('ViewsEviValueToken', "replacements-$ui", $replacements);

    return $replacements;
  }

}
