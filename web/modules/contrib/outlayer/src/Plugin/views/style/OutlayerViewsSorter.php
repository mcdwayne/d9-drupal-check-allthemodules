<?php

namespace Drupal\outlayer\Plugin\views\style;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;
use Drupal\blazy\Blazy;
use Drupal\outlayer\OutlayerDefault;
use Drupal\outlayer\OutlayerHook;

/**
 * Outlayer style plugin for Isotope sorter.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "outlayer_sorter",
 *   title = @Translation("Outlayer Sorter"),
 *   help = @Translation("Display the results in an Outlayer sorter."),
 *   theme = "item_list",
 *   register_theme = FALSE,
 *   display_types = {"normal"}
 * )
 */
class OutlayerViewsSorter extends OutlayerViewsBase {

  /**
   * Sorter fields.
   *
   * @var array
   */
  protected $sorterItems = [];

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = [];
    foreach (OutlayerDefault::viewsSorterSettings() as $key => $value) {
      $options[$key] = ['default' => $value];
    }
    return $options + parent::defineOptions();
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $fields                      = ['classes'];
    $definition                  = $this->getDefinedFieldOptions($fields);
    $definition['outlayers']     = $this->getViewsAsOptions('outlayer_isotope');
    $definition['opening_class'] = 'form--views';

    $count = count($definition['classes']);
    $definition['captions_count'] = $count;

    $this->admin()->sorterForm($form, $definition);
    $this->admin()->closingForm($form, $definition);

    $form['sorters']['#attributes']['class'][] = 'form-wrapper--caption';
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $settings = $this->buildSettings();
    $instance = $settings['instance_id'];
    $plugin_id = $settings['plugin_id'];

    // All views must have the same instance in order to coordinate.
    if (!empty($settings['outlayer'])) {
      list($main_view_name, $main_view_display) = explode(":", $settings['outlayer'], 2);
      $instance = str_replace('_', '-', "{$main_view_name}-{$main_view_display}");
    }

    $settings['id'] = Blazy::getHtmlId("{$plugin_id}-{$instance}");
    $settings['instance_id'] = $instance;
    $settings['sorters'] = array_filter($settings['sorters']);

    if (empty($settings['sorters'])) {
      return [];
    }

    $elements = [];
    foreach ($this->renderGrouping($this->view->result, $settings['grouping']) as $rows) {
      $items = $this->buildElements($settings, $rows);

      $element = $this->buildItemList($items, $settings, 'sorter');
      $element['#attributes']['data-sorters'] = Json::encode(array_keys($this->sorterItems));

      $elements[] = $element;
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function buildElements($settings, $rows) {
    $build = [];

    if (!empty($settings['sort_by'])) {
      $variables = [
        'classes' => ['button--reset', 'button--sorter', 'is-active'],
        'sorter'  => str_replace(' ', '-', mb_strtolower($settings['sort_by'])),
        'title'   => str_replace('-', ' ', $settings['sort_by']),
      ];

      $build['all'] = OutlayerHook::button($variables);
    }

    foreach ($settings['sorters'] as $field_name => $sorter) {
      $title = str_replace('field_', '', $field_name);
      $sorter_key = str_replace('_', '', $title);
      $variables = [
        'classes' => ['button--sorter'],
        'sorter'  => $sorter_key,
        'title'   => str_replace('_', ' ', $title),
      ];

      $build[] = OutlayerHook::button($variables);

      $this->sorterItems[$sorter_key] = $field_name;
    }

    unset($this->view->row_index);
    return $build;
  }

}
