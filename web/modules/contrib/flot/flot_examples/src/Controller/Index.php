<?php

namespace Drupal\flot_examples\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Table of contents for the FLOT Example Module.
 */
class Index extends ControllerBase {

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a MyController object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler')
    );
  }

  /**
   * Function content.
   */
  public function content() {

    $options = [':one' => 'http://www.flotcharts.org'];
    $output[] = [
      '#markup' => $this->t('Here are some examples for <a href=":one">Flot</a>, the Javascript charting library for jQuery:', $options),
    ];
    $basic_items[] = Link::createFromRoute('Basic Example', 'flot_examples.basic_usage');

    $options = [
      ':one' => Url::fromRoute('flot_examples.series_types')->toString(),
      ':two' => Url::fromRoute('flot_examples.categories')->toString(),
    ];
    $basic_items[] = $this->t('<a href=":one">Different graph types</a> and <a href=":two">simple categories/textual data</a>', $options);

    $options = [
      ':one' => Url::fromRoute('flot_examples.basic_options')->toString(),
      ':two' => Url::fromRoute('flot_examples.annotating')->toString(),
    ];
    $basic_items[] = $this->t('<a href=":one">Setting various options</a> and <a href=":two">annotating a chart</a>', $options);

    $options = [
      ':one' => Url::fromRoute('flot_examples.ajax')->toString(),
      ':two' => Url::fromRoute('flot_examples.realtime')->toString(),
    ];
    $basic_items[] = $this->t('<a href=":one">Updating graphs with AJAX</a> and <a href=":two">real-time updates</a>', $options);
    $output['basic'] = [
      '#title' => 'Basic Usage',
      '#theme' => 'item_list',
      '#items' => $basic_items,
    ];

    $inter_items[] = Link::createFromRoute('Turning series on/off', 'flot_examples.series_toggle');

    $options = [
      ':one' => Url::fromRoute('flot_examples.selection')->toString(),
      ':two' => Url::fromRoute('flot_examples.zooming')->toString(),
    ];
    $inter_items[] = $this->t('<a href=":one">Rectangular selection support and zooming</a> and <a href=":two">zooming with overview</a> (both with selection plugin)', $options);

    $inter_items[] = Link::createFromRoute('Interacting with the data points', 'flot_examples.interacting');

    $options = [
      ':one' => Url::fromRoute('flot_examples.navigate')->toString(),
    ];
    $inter_items[] = $this->t('<a href=":one">Panning and zooming</a> (with navigation plugin)', $options);

    $options = [
      ':one' => Url::fromRoute('flot_examples.resize')->toString(),
    ];
    $inter_items[] = $this->t('<a href=":one">Automatically redraw when window is resized</a> (with resize plugin)', $options);
    $output['interactivity'] = [
      '#title' => 'Interactivity',
      '#theme' => 'item_list',
      '#items' => $inter_items,
    ];

    $options = [
      ':one' => Url::fromRoute('flot_examples.symbols')->toString(),
    ];
    $additional_items[] = $this->t('<a href=":one">Using other symbols than circles for points</a> (with symbol plugin)', $options);

    $options = [
      ':one' => Url::fromRoute('flot_examples.axes-time')->toString(),
      ':two' => Url::fromRoute('flot_examples.visitors')->toString(),
    ];
    $additional_items[] = $this->t('<a href=":one">Plotting time series</a>, <a href=":two">visitors per day with zooming and weekends</a> (with selection plugin).', $options);
    $options = [
      ':one' => Url::fromRoute('flot_examples.axes-multiple')->toString(),
      ':two' => Url::fromRoute('flot_examples.axes_interacting')->toString(),
    ];
    $additional_items[] = $this->t('<a href=":one">Multiple axes</a> and <a href=":two">interacting with the axes</a>', $options);

    $options = [
      ':one' => Url::fromRoute('flot_examples.threshold')->toString(),
    ];
    $additional_items[] = $this->t('<a href=":one">Thresholding the data</a> (with threshold plugin)', $options);

    $options = [
      ':one' => Url::fromRoute('flot_examples.stacking')->toString(),
    ];
    $additional_items[] = $this->t('<a href=":one">Stacked charts</a> (with stacking plugin)', $options);

    $options = [
      ':one' => Url::fromRoute('flot_examples.percentiles')->toString(),
    ];
    $additional_items[] = $this->t('<a href=":one">Using filled areas to plot percentiles</a> (with fillbetween plugin)', $options);

    $options = [
      ':one' => Url::fromRoute('flot_examples.tracking')->toString(),
    ];
    $additional_items[] = $this->t('<a href=":one">Tracking curves with crosshair</a> (with crosshair plugin)', $options);
    $options = [
      ':one' => Url::fromRoute('flot_examples.image')->toString(),
    ];
    $additional_items[] = $this->t('<a href=":one">Plotting prerendered images</a> (with image plugin)', $options);

    $options = [
      ':one' => Url::fromRoute('flot_examples.series_errorbars')->toString(),
    ];
    $additional_items[] = $this->t('<a href=":one">Plotting error bars</a> (with errorbars plugin)', $options);

    $options = [
      ':one' => Url::fromRoute('flot_examples.series_pie')->toString(),
    ];
    $additional_items[] = $this->t('<a href=":one">Pie charts</a> (with pie plugin)', $options);

    $options = [
      ':one' => Url::fromRoute('flot_examples.canvas')->toString(),
    ];
    $additional_items[] = $this->t('<a href=":one">Rendering text with canvas instead of HTML</a> (with canvas plugin)', $options);

    $output['additional'] = [
      '#title' => 'Additional Features',
      '#theme' => 'item_list',
      '#items' => $additional_items,
    ];

    $plugin_items = [];

    // Call modules that implement the hook, and let them add items.
    $this->moduleHandler()->alter('flot_examples_toc', $plugin_items);
    if (count($plugin_items > 0)) {
      $output['plugins'] = [
        '#title' => 'Plugins',
        '#theme' => 'item_list',
        '#items' => $plugin_items,
      ];
    }
    return $output;
  }

}
