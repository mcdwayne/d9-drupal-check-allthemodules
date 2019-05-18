<?php

namespace Drupal\insert_view_adv\Plugin\Filter;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\views\Views;

/**
 * Provides a filter for insert view.
 *
 * @Filter(
 *   id = "insert_view_adv",
 *   module = "insert_view_adv",
 *   title = @Translation("Advanced Insert View"),
 *   description = @Translation("Allows to embed views using the simple syntax:[view:name=display=args]"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 *   settings = {
 *     "allowed_views" = {},
 *     "render_as_empty" = 0,
 *   }
 * )
 */
class InsertView extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $matches = [];
    // Encode configuration to path to build method because lazy_loader only works with scalar arguments
    $encoded_configuration = Json::encode($this->getConfiguration());
    $result = new FilterProcessResult($text);
    // Check first the direct input of shortcode.
    $count = preg_match_all("/\[view:([^=\]]+)=?([^=\]]+)?=?([^\]]*)?\]/i", $text, $matches);
    if ($count) {
      $search = $replace = [];
      foreach ($matches[0] as $key => $value) {
        $view_name = $matches[1][$key];
        $display_id = ($matches[2][$key] && !is_numeric($matches[2][$key])) ? $matches[2][$key] : 'default';
        $args = $matches[3][$key];
        $view_output = $result->createPlaceholder('\Drupal\insert_view_adv\Plugin\Filter\InsertView::build', [
          $view_name,
          $display_id,
          $args,
          $encoded_configuration
        ]);
        $search[] = $value;
        $replace[] = $view_output;
      }
      $text = str_replace($search, $replace, $text);
    }
    // Check the view inserted from the CKeditor plugin.
    $count = preg_match_all('/(<p>)?(?<json>{(?=.*inserted_view_adv\b)(?=.*arguments\b)(.*)})(<\/p>)?/', $text, $matches);
    if ($count) {
      $search = $replace = [];
      foreach ($matches['json'] as $key => $value) {
        $inserted = Json::decode($value);
        if (!is_array($inserted) || empty($inserted)) {
          continue;
        }
        $view_parts = explode('=', $inserted['inserted_view_adv']);
        if (empty($view_parts)) {
          continue;
        }
        $view_name = $view_parts[0];
        $display_id = ($view_parts[1] && !is_numeric($view_parts[1])) ? $view_parts[1] : 'default';
        $args = '';
        if (!empty($inserted['arguments'])) {
          $args = implode('/', $inserted['arguments']);
        }
        $view_output = $result->createPlaceholder('\Drupal\insert_view_adv\Plugin\Filter\InsertView::build', [
          $view_name,
          $display_id,
          $args,
          $encoded_configuration
        ]);
        $search[] = $value;
        $replace[] = $view_output;
      }
      $text = str_replace($search, $replace, $text);
    }
    $result->setProcessedText($text)->addCacheTags(['insert_view_adv'])->addCacheContexts(['url', 'user.permissions']);

    return $result;
  }

  /**
   * Builds the view markup from the data received from the token.
   *
   * @param string $view_name
   *   The machine name of the view.
   * @param string $display_id
   *   The name of the display to show.
   * @param string $args
   *   The arguments that are passed to the view in format arg1/arg2/arg3/... .
   * @param string $configuration
   *   Json encoded string of the filter configuration.
   *
   * @return array
   *   The rendered array of the view to display.
   */
  static public function build($view_name, $display_id, $args, $configuration) {
    $plain = '';
    // Just in case check if this is an array already.
    if (!is_array($configuration)) {
      $configuration = Json::decode($configuration);
    }
    // Check what to do if the render array is empty and there is nothing to show.
    if ($configuration && isset($configuration['settings']['render_as_empty']) && $configuration['settings']['render_as_empty'] == 0) {
      $plain = '[view:' . $view_name . '=' . $display_id;
      if (!empty($args)) {
        $plain .= '=' . implode('/', $args);
      }
      $plain .= ']';
    }
    // Do nothing if there is no view name provided.
    if (empty($view_name)) {
      return ['#attached' => [], '#markup' => $plain];
    }
    // Do not render the views that are not allowed to be printed.
    if ($configuration && !empty($configuration['settings']['allowed_views'])) {
      $allowed_views = array_filter($configuration['settings']['allowed_views']);
      if (!empty($allowed_views) && empty($allowed_views[$view_name . '=' . $display_id])) {
        return ['#attached' => [], '#markup' => $plain];
      }
    }
    // Get the view.
    $view = Views::getView($view_name);
    if (empty($view)) {
      return ['#attached' => [], '#markup' => $plain];
    }
    // Check if the current user has access to the given view.
    if (!$view->access($display_id)) {
      return ['#attached' => [], '#markup' => $plain];
    }
    // Try to get the arguments from the current path.
    $current_path = \Drupal::service('path.current')->getPath();
    $url_args = explode('/', $current_path);
    foreach ($url_args as $id => $arg) {
      $args = str_replace("%$id", $arg, $args);
    }
    $args = preg_replace(',/?(%\d),', '', $args);
    $args = $args ? explode('/', $args) : [];

    return $view->preview($display_id, $args);
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    if ($long) {
      $examples = [
        '[view:my_view]',
        '[view:my_view=my_display]',
        '[view:my_view=my_display=arg1/arg2/arg3]',
        '[view:my_view==arg1/arg2/arg3]',
      ];
      $items = [
        $this->t('Insert view filter allows to embed views using tags. The tag syntax is relatively simple: [view:name=display=args]'),
        $this->t('For example [view:tracker=page=1] says, embed a view named "tracker", use the "page" display, and supply the argument "1".'),
        $this->t('The <em>display</em> and <em>args</em> parameters can be omitted. If the display is left empty, the view\'s default display is used.'),
        $this->t('Multiple arguments are separated with slash. The <em>args</em> format is the same as used in the URL (or view preview screen).'),
        [
          'data' => $this->t('Valid examples'),
          'children' => $examples,
        ],
      ];
      $list = [
        '#type' => 'item_list',
        '#items' => $items,
      ];
      return render($list);
    }
    else {
      return $this->t('You may use [view:<em>name=display=args</em>] tags to display views.');
    }
  }

  /**
   * {@inheritDoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    $views_list = Views::getEnabledViews();
    $options = [];
    foreach ($views_list as $machine_name => $view) {
      foreach ($view->get('display') as $display) {
        $display_title = !empty($display['display_options']['title']) ? $display['display_options']['title'] : $display['display_title'];
        $options[$machine_name . '=' . $display['id']] = $this->t('@view_name: @display_title (@display_name)', ['@view_name' => $view->label(), '@display_title' => $display_title, '@display_name' => $display['id']]);
      }
    }
    $form['allowed_views'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Allowed views to insert'),
      '#description' => $this->t('Leave empty to allow all views'),
      '#options' => $options,
      '#default_value' => $this->settings['allowed_views'],
    ];
    $form['render_as_empty'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Do not render disabled/not allowed views'),
      '#default_value' => $this->settings['render_as_empty'],
      '#description' => $this->t('If unchecked the disabled/not allowed view will be rendered as token [view:view_name=display_id=args]'),
    ];
    return $form;
  }

}
