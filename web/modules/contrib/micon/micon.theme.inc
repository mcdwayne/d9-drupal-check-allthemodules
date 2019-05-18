<?php

/**
 * @file
 * Contains micon.theme.inc.
 */

use Drupal\Core\Render\Element;
use Drupal\micon\MiconIconInterface;

/**
 * Prepares variables for icon and text display.
 *
 * Default template: micon.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the icon
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_micon(array &$variables) {
  $icon = $variables['icon'];
  // Allow icon_id to be used as #icon.
  if (is_string($icon)) {
    $icon = \Drupal::service('micon.icon.manager')->getIconMatch($icon);
  }
  if ($icon instanceof MiconIconInterface) {
    $variables['micon'] = $icon;
    $variables['icon'] = $variables['micon']->setAttributes($variables['icon_attributes'])->toRenderable();
  }
  else {
    // No icon found. We don't want to render anything.
    $variables['icon'] = '';
  }
}

/**
 * Prepares variables for the Micon icon template.
 *
 * Default template: micon-icon-font.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the icon
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_micon_icon(array &$variables) {
  $icon = $variables['icon'];
  $variables['tag'] = 'span';
  $variables['attributes']['class'][] = 'micon';
  // Allow icon_id to be used as #icon.
  if (is_string($icon)) {
    $icon = \Drupal::service('micon.icon.manager')->getIconMatch($icon);
  }
  if ($icon instanceof MiconIconInterface) {
    $variables['icon'] = $icon;
    $variables['type'] = $icon->getType();
    $variables['tag'] = $icon->getWrappingElement();
    $variables['attributes']['class'][] = $icon->getSelector();
    $variables['attributes']['aria-hidden'] = 'true';
    $variables['children'] = $icon->getChildren();
  }
}

/**
 * Prepares variables for Micon package templates.
 *
 * Default template: micon-package.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the icon
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_micon_package(array &$variables) {
  $variables['micon'] = $micon = $variables['element']['#micon'];
  $variables['type'] = $micon->type();

  // Helpful $content variable for templates.
  $variables['content'] = [];
  foreach (Element::children($variables['element']) as $key) {
    $variables['content'][$key] = $variables['element'][$key];
  }

  $function = 'micon_template_preprocess_micon_package__' . $variables['type'];
  if (function_exists($function)) {
    $function($variables);
  }
}

/**
 * Prepares variables for Micon package templates of type ICON.
 */
function micon_template_preprocess_micon_package__font(&$variables) {
  $micon = $variables['micon'];
  $info = $micon->getInfo();

  $variables['content']['use'] = [
    '#theme' => 'fieldset',
    '#title' => t('Usage Information'),
  ];

  $variables['content']['use']['#children']['details'] = [
    '#markup' => '<textarea rows="13" class="micon-usage">
/* use !important to prevent issues with browser extensions that change fonts */
font-family: \'' . $info['metadata']['name'] . '\' !important;
speak: none;
font-style: normal;
font-weight: normal;
font-variant: normal;
text-transform: none;
line-height: 1;

/* Better Font Rendering =========== */
-webkit-font-smoothing: antialiased;
-moz-osx-font-smoothing: grayscale;</textarea>',
    '#allowed_tags' => ['textarea'],
  ];

  $variables['content']['icons'] = [
    '#theme' => 'fieldset',
    '#title' => t('Available Icons'),
  ];
  $variables['content']['icons']['#children']['icons'] = [
    '#theme' => 'micon_icon_list',
    '#micon' => $micon,
  ];
}

/**
 * Prepares variables for Micon package templates of type SVG.
 */
function micon_template_preprocess_micon_package__image(array &$variables) {
  $micon = $variables['micon'];

  $variables['content']['icons'] = [
    '#theme' => 'fieldset',
    '#title' => t('Available SVGs'),
  ];
  $variables['content']['icons']['#children']['icons'] = [
    '#theme' => 'micon_icon_list',
    '#micon' => $micon,
  ];
}

/**
 * Prepares variables for the Micon icon list template.
 *
 * Default template: micon-icon-list.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the icon
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_micon_icon_list(array &$variables) {
  $variables['micon'] = $micon = $variables['element']['#micon'];
  $variables['icons'] = [];

  $variables['content']['filters'] = array(
    '#type' => 'container',
    '#attributes' => array(
      'class' => array('table-filter', 'js-show'),
    ),
  );

  $variables['content']['filters']['text'] = array(
    '#type' => 'search',
    '#title' => t('Filter icons'),
    '#title_display' => 'invisible',
    '#size' => 30,
    '#placeholder' => t('Filter by name or description'),
    '#description' => t('Enter a part of the icon name.'),
    '#description_display' => 'after',
    '#attributes' => array(
      'class' => array('micon-filter-text'),
      'data-list' => '.micon-icon-list',
      'autocomplete' => 'off',
    ),
  );

  foreach ($micon->getIcons() as $key => $icon) {
    $variables['icons'][$key]['preview'] = $icon->toRenderable();
    $variables['icons'][$key]['selector'] = [
      '#type' => 'textfield',
      '#value' => $icon->getSelector(),
      '#attributes' => ['class' => ['selector']],
    ];
    $variables['icons'][$key]['search']['#markup'] = '<div class="micon-filter-text visually-hidden">' . $icon->getTag() . '</div>';
    if ($icon->getType() == 'font') {
      // Show hex for CSS implementation.
      $variables['icons'][$key]['hex'] = [
        '#type' => 'textfield',
        '#value' => $icon->getHex(),
        '#attributes' => ['class' => ['info']],
      ];
    }
    if ($icon->getType() == 'image') {
      // Show renderable markup.
      $render = $variables['icons'][$key]['preview'];
      $rendered = \Drupal::service('renderer')->render($render)->__toString();
      $rendered = preg_replace('/<!--(.|\s)*?-->/', '', $rendered);
      $variables['icons'][$key]['markup'] = [
        '#type' => 'textfield',
        '#value' => $rendered,
        '#attributes' => ['class' => ['info']],
      ];
    }
  }
}
