<?php

/**
 * @file
 * Implements help hooks for the module.
 */

use Drupal\Core\Routing\RouteMatchInterface;

use Drupal\chart_suite\Branding;

/**
 * Implements hook_help().
 *
 * This function responds to the help route for the Chart Suite module
 * and provides a single page with multiple sections to document the
 * purpose and uses of the module.
 */
function chart_suite_help(string $routeName, RouteMatchInterface $routeMatch) {
  //
  // The function returns a render array containing multiple help sections,
  // partly mandated by Drupal best practices.
  //
  // Check the route.
  if ($routeName !== 'help.page.chart_suite') {
    // The route is not part of our help page. Do nothing.
    return;
  }

  // Get module information.
  $moduleTitle = \Drupal::moduleHandler()->getName('chart_suite');
  $imagePath = '/' . drupal_get_path('module', 'chart_suite') . '/images/';

  //
  // About
  // -----
  // This section is recommended by Drupal best practices.
  $help = '';
  $help .= '<h4>' . t('About') . '</h4>';
  $help .= '<p>' . t(
    '<strong>@moduleTitle</strong> provides file field formatters to chart file data for tables, trees, and graphs. A variety of well-known textual file formats are supported, including comma-separated values (CSV), tab-separated values (TSV), HTML tables, and JSON tables, trees, and graphs in common array- and object-style schemas.',
    [
      '@moduleTitle' => $moduleTitle,
    ]) . '</p>';

  //
  // Uses
  // ----
  // This section is recommended by Drupal best practices.
  $help .= '<h4>' . t('Uses') . '</h4>';
  $help .= '<p>' . t(
    "Web site adminstrators may use the <strong>@moduleTitle</strong> module, along with the core <strong>Field UI</strong> and <strong>View UI</strong> modules, to configure the way in which a file field's file data is presented. Once configured, the formatter automatically reads and presents interactive charts of a file's content when the user views a page containing the file field. Charts support line and area plots, scatter plots, bar and pie charts, and tree diagrams.",
    [
      '@moduleTitle' => $moduleTitle,
    ]) . '</p>';

  //
  // Formatters
  // ----------
  // List the formatters.
  //
  // Datetime.
  $help .= '<h4>' . t('Formatters for files') . '</h4>';
  $help .= '<dl class="chart_suite-formatter-list">';

  // -------------------------------------------------------------------.
  $help .= '<dt>' . t('Chart Suite') . '</dt>';
  $image = '<img src="' . $imagePath . 'ChartSuite.png">';
  $help .= '<dd><em>' . $image . t('Supports: file fields.') . '</em></dd>';
  $help .= '<dd>' . t("The formatter presents interactive charts of tables, trees, and graphs read from a field's file. The range of chart types offered to the user varies depending upon the type of data contained in a file being viewed.") . '</dd>';
  $help .= '<dd>' . t("Settings select whether to show a linked file name below the chart, and whether to disable the entire formatter if the field's file cannot be read.") . '</dd>';

  //
  // Render element
  // --------------
  // Return a render element instead of the HTML string itself. This is
  // needed in order to include module libraries to style the help page.
  $page = [
    '#attached'  => [
      'library'  => [
        'chart_suite.core',
        'chart_suite/chart_suite.help',
      ],
    ],
    'page'       => [
      '#type'    => 'container',
      '#tree'    => TRUE,
      '#attributes' => [
        'class'     => ['chart_suite-admin-help'],
      ],
      'branding' => Branding::getBannerBranding(),
      'help'     => [
        '#type'  => 'html_tag',
        '#tag'   => 'div',
        '#value' => $help,
      ],
    ],
  ];

  return $page;
}
