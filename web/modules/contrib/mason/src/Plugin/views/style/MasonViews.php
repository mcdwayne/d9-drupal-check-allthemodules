<?php

namespace Drupal\mason\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\blazy\Blazy;
use Drupal\blazy\BlazyManagerInterface;
use Drupal\blazy\Dejavu\BlazyDefault;
use Drupal\blazy\Dejavu\BlazyStylePluginBase;
use Drupal\mason\Entity\Mason;
use Drupal\mason\MasonManagerInterface;

/**
 * Mason style plugin.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "mason",
 *   title = @Translation("Mason"),
 *   help = @Translation("Display the results in a Mason."),
 *   theme = "mason",
 *   register_theme = FALSE,
 *   display_types = {"normal"}
 * )
 */
class MasonViews extends BlazyStylePluginBase {

  /**
   * The mason service manager.
   *
   * @var \Drupal\mason\MasonManagerInterface
   */
  protected $manager;

  /**
   * Constructs a MasonManager object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, BlazyManagerInterface $blazy_manager, MasonManagerInterface $manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $blazy_manager);
    $this->manager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('blazy.manager'), $container->get('mason.manager'));
  }

  /**
   * Returns the mason admin.
   */
  public function admin() {
    return \Drupal::service('mason.admin');
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = ['fillers' => ['default' => ''], 'stamp' => ['default' => []]];
    foreach (BlazyDefault::extendedSettings() as $key => $value) {
      $options[$key] = ['default' => $value];
    }
    return $options + parent::defineOptions();
  }

  /**
   * Overrides parent::buildOptionsForm().
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $fields = [
      'captions',
      'layouts',
      'images',
      'links',
      'titles',
      'classes',
    ];
    $definition = $this->getDefinedFieldOptions($fields);

    $this->admin()->buildSettingsForm($form, $definition);

    $count = count($definition['captions']);
    $wide = $count > 2 ? ' form--wide form--caption-' . $count : ' form--caption-' . $count;

    $title = '<p class="form__header form__title">';
    $title .= $this->t('Check Vanilla if using content/custom markups, not fields. <small>See it under <strong>Format > Show</strong> section. Otherwise Mason markups apply which require some fields added below.</small>');
    $title .= '</p>';
    $form['opening']['#markup'] = '<div class="form--mason form--slick form--views form--half form--vanilla has-tooltip' . $wide . '">' . $title;
    $form['image']['#description'] .= ' ' . $this->t('Be sure to UNCHECK "Use field template" to have it work for Blazy lazyloading.');
  }

  /**
   * Overrides StylePluginBase::render().
   */
  public function render() {
    $blazy     = $this->blazyManager();
    $view      = $this->view;
    $settings  = $this->options + BlazyDefault::entitySettings();
    $view_name = $view->storage->id();
    $view_mode = $view->current_display;
    $count     = count($view->result);
    $id        = Blazy::getHtmlId("mason-views-{$view_name}-{$view_mode}", $settings['id']);
    $optionset = Mason::load($settings['optionset']);

    $settings += [
      'cache_metadata' => [
        'keys' => [$id, $view_mode, $settings['optionset']],
      ],
      'count' => $count,
      'current_view_mode' => $view_mode,
      'view_name' => $view_name,
    ];

    $settings['id']        = $id;
    $settings['item_id']   = 'box';
    $settings['caption']   = array_filter($settings['caption']);
    $settings['namespace'] = 'mason';
    $settings['ratio']     = '';
    $settings['_views']    = TRUE;

    $elements = [];
    foreach ($this->renderGrouping($view->result, $settings['grouping']) as $rows) {
      $element = $this->buildElements($settings, $rows);

      // Supports Blazy formatter multi-breakpoint images if available.
      $blazy->isBlazy($settings, $element[0]);

      $build = [
        'items'     => $element,
        'optionset' => $optionset,
        'settings'  => $settings,
      ];

      $elements = $this->manager->build($build);
    }

    return $elements;
  }

  /**
   * Returns mason contents.
   */
  public function buildElements(array $settings, $rows) {
    $build   = [];
    $view    = $this->view;
    $item_id = $settings['item_id'];

    foreach ($rows as $index => $row) {
      $view->row_index = $index;

      $box             = [];
      $box['delta']    = $index;
      $box[$item_id]   = [];
      $box['settings'] = $settings;

      if (!empty($settings['class'])) {
        $classes = $this->getFieldString($row, $settings['class'], $index);
        $box['settings']['class'] = empty($classes[$index]) ? [] : $classes[$index];
      }

      // Use Vanilla mason if so configured, ignoring Mason markups.
      if (!empty($settings['vanilla'])) {
        $box[$item_id] = $view->rowPlugin->render($row);
      }
      else {
        // Build individual row/element contents.
        $this->buildElement($box, $row, $index);
      }

      // Build mason items.
      $build[] = $box;
      unset($box);
    }

    unset($view->row_index);
    return $build;
  }

}
