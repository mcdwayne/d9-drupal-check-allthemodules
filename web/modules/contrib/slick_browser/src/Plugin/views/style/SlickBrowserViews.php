<?php

namespace Drupal\slick_browser\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\style\StylePluginBase;
use Drupal\blazy\Blazy;
use Drupal\blazy\BlazyManagerInterface;
use Drupal\slick\SlickManagerInterface;
use Drupal\slick_browser\SlickBrowserDefault;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Slick Browser style plugin.
 */
class SlickBrowserViews extends StylePluginBase {

  /**
   * {@inheritdoc}
   */
  protected $usesRowPlugin = TRUE;

  /**
   * {@inheritdoc}
   */
  protected $usesGrouping = FALSE;

  /**
   * The blazy manager service.
   *
   * @var \Drupal\blazy\BlazyManagerInterface
   */
  protected $blazyManager;

  /**
   * The slick service manager.
   *
   * @var \Drupal\slick\SlickManagerInterface
   */
  protected $manager;

  /**
   * Constructs a SlickManager object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, BlazyManagerInterface $blazy_manager, SlickManagerInterface $manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->blazyManager = $blazy_manager;
    $this->manager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('blazy.manager'), $container->get('slick.manager'));
  }

  /**
   * Returns the slick admin.
   */
  public function admin() {
    return \Drupal::service('slick.admin');
  }

  /**
   * Returns the blazy manager.
   */
  public function blazyManager() {
    return $this->blazyManager;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = [];
    foreach (SlickBrowserDefault::viewsSettings() as $key => $value) {
      $options[$key] = ['default' => $value];
    }
    return $options + parent::defineOptions();
  }

  /**
   * Overrides StylePluginBase::buildOptionsForm().
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $definition = [
      'caches'            => FALSE,
      'current_view_mode' => $this->view->current_display,
      'namespace'         => 'slick',
      'grid_form'         => TRUE,
      'settings'          => $this->options,
      'style'             => TRUE,
    ];

    // @todo: Adds field handlers to reduce configuration if time permits.
    $this->admin()->buildSettingsForm($form, $definition);
    unset($form['layout']);

    $title = '<p class="form__header form__title">';
    $title .= $this->t('Use filter Slick Browser to have a view switcher. <small>Add one under <strong>Filter criteria</strong> section.</small>');
    $title .= '</p>';
    $form['opening']['#markup'] = '<div class="form--slick form--style form--views form--half has-tooltip">' . $title;

    if (isset($form['style']['#description'])) {
      $form['style']['#description'] .= ' ' . $this->t('Ignored if Slick Browser view filter has only list (table-like) enabled.');
    }
  }

  /**
   * Overrides StylePluginBase::render().
   */
  public function render() {
    $view      = $this->view;
    $count     = count($view->result);
    $settings  = $this->options;
    $view_name = $view->storage->id();
    $view_mode = $view->current_display;
    $id        = Blazy::getHtmlId("sb-{$view_name}-{$view_mode}");

    $settings += [
      'cache_metadata'    => [
        'keys' => [$id, $view_mode, $settings['optionset']],
      ],
      'count'             => $count,
      'current_view_mode' => $view_mode,
      'view_name'         => $view_name,
    ];

    $settings['_browser']     = TRUE;
    $settings['id']           = $id;
    $settings['item_id']      = 'slide';
    $settings['namespace']    = 'slick';
    $settings['overridables'] = array_filter($settings['overridables']);

    $elements = [];
    foreach ($this->renderGrouping($view->result, $settings['grouping']) as $rows) {
      $build = $this->buildElements($settings, $rows);

      // Supports Blazy formatter multi-breakpoint images if available.
      $this->blazyManager()->isBlazy($settings, $build['items'][0]);

      $build['settings'] = $settings;

      // Attach media assets if a File with potential videos, or Media entity.
      if (in_array($view->getBaseEntityType()->id(), ['file', 'media'])) {
        $build['attached']['library'][] = 'slick_browser/media';
      }
      $elements = $this->manager->build($build);
      unset($build);
    }
    return $elements;
  }

  /**
   * Returns slick contents.
   */
  public function buildElements(array $settings, $rows) {
    $build = [];

    foreach ($rows as $index => $row) {
      $this->view->row_index = $index;

      $settings['delta'] = $index;

      $slide = [
        'settings' => $settings,
        'slide' => $this->view->rowPlugin->render($row),
      ];

      $build['items'][$index] = $slide;
      unset($slide);
    }
    unset($this->view->row_index);

    return $build;
  }

}
