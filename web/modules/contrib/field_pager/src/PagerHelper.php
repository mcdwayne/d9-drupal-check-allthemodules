<?php

namespace Drupal\field_pager;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;

/**
 * Helper functions for field_pager.
 */
class PagerHelper {

  /**
   * Add default settings.
   */
  public static function mergeDefaultSettings($settings = []) {

    return [
      'index_name' => 'page',
      'pager_np' => 1,
      'pager_fl' => 1,
      'pager_nb' => 1,
      'pager_max' => 5,
      'summary' => 0,
    ] + $settings;

  }

  /**
   * Add settings to the form.
   */
  public static function mergeSettingsForm(array $form, FormStateInterface $form_state, $instance, $elements = []) {
    $elements['summary'] = [
      '#type' => 'checkbox',
      '#title' => t('Show summary'),
      '#description' => t('Show text "Page X of N"'),
      '#default_value' => $instance->getSetting('summary'),
    ];
    $elements['index_name'] = [
      '#type' => 'textfield',
      '#title' => t('Index field name'),
      '#default_value' => $instance->getSetting('index_name'),
      '#required' => TRUE,
    ];
    $elements['pager_np'] = [
      '#type' => 'checkbox',
      '#title' => t('Show Next & Previus'),
      '#default_value' => $instance->getSetting('pager_np'),
    ];
    $elements['pager_fl'] = [
      '#type' => 'checkbox',
      '#title' => t('Show First & Last'),
      '#default_value' => $instance->getSetting('pager_fl'),
    ];
    $elements['pager_nb'] = [
      '#type' => 'checkbox',
      '#title' => t('Show All pages'),
      '#default_value' => $instance->getSetting('pager_nb'),
    ];
    $elements['pager_max'] = [
      '#type' => 'number',
      '#title' => t('Maximum number of pages'),
      '#default_value' => $instance->getSetting('pager_max'),
      '#states' => [
        'visible' => [
          ':input[name$="[settings_edit_form][settings][pager_nb]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return $elements;
  }

  /**
   * Add settings summary.
   */
  public static function mergeSettingsSummary($summary, $instance) {
    $pager_max = $instance->getSetting('pager_max');
    $summary[] = "Pager ($pager_max)";
    return $summary;
  }

  /**
   * Add View.
   */
  public static function mergeView($nb_items, $instance, $fields, $settings = []) {

    // No pager for 1 item.
    if ($nb_items < 2) {
      return $fields;
    }

    // Config.
    $index_name = $instance->getSetting('index_name');
    $pager_np = $instance->getSetting('pager_np');
    $pager_fl = $instance->getSetting('pager_fl');
    $pager_nb = $instance->getSetting('pager_nb');
    $pager_max = $instance->getSetting('pager_max');
    $summary = $instance->getSetting('summary');
    $index_current = (int) (isset($_GET[$index_name]) ? $_GET[$index_name] : 0);

    $elements = [];
    if ($summary) {
      $elements['summary'] = [
        '#markup' => new TranslatableMarkup("Page @index of @total", [
          '@index' => $index_current + 1,
          '@total' => $nb_items,
        ]),
        '#prefix' => '<div>',
        '#suffix' => '</div>',
      ];
    }
    $elements['fields'] = $fields;

    // Handle cache @todo
    $elements['#cache']['max-age'] = 0;

    // Set next / Previus index.
    $index_prev = $index_current ? $index_current - 1 : 0;
    $index_next = ($index_current + 1) < $nb_items ? ($index_current + 1) : 0;
    $lower_limit = $upper_limit = min($index_current, $nb_items);

    $items = [];

    $url = Url::fromRoute('<current>');

    // Search boundaries.
    for ($b = 0; $b < $pager_max && $b < $nb_items;) {
      if ($lower_limit > 0) {
        $lower_limit--;
        $b++;
      }
      if ($b < $pager_max && $upper_limit < $nb_items) {
        $upper_limit++;
        $b++;
      }
    }

    if ($pager_fl) {
      $items[] = [
        'text' => new TranslatableMarkup("First"),
        'attributes' => [],
        'href' => $url->setRouteParameter($index_name, 0)->toString(),
      ];
    }
    if ($pager_np) {
      $items[] = [
        'text' => new TranslatableMarkup("Previous"),
        'attributes' => [],
        'href' => $url->setRouteParameter($index_name, $index_prev)->toString(),
      ];
    }

    if ($pager_nb) {
      for ($i = $lower_limit; $i < $upper_limit; $i++) {
        $attributes = [];
        if ($i == $index_current) {
          $attributes['class'][] = "active";
        }
        if ($i == 0) {
          $attributes['class'][] = "first";
        }
        if ($i == $nb_items - 1) {
          $attributes['class'][] = "last";
        }

        // Add $items.
        $items[] = [
          'text' => ($i + 1),
          'attributes' => $attributes,
          'href' => $url->setRouteParameter($index_name, $i)->toString(),
        ];

      }
    }

    if ($pager_np) {
      $items[] = [
        'text' => new TranslatableMarkup("Next"),
        'attributes' => [],
        'href' => $url->setRouteParameter($index_name, $index_next)->toString(),
      ];
    }
    if ($pager_fl) {
      $items[] = [
        'text' => new TranslatableMarkup("Last"),
        'attributes' => [],
        'href' => $url->setRouteParameter($index_name, $nb_items - 1)->toString(),
      ];
    }

    $elements['custom_pager'] = [
      '#theme' => 'field_pager',
      '#index' => $index_current,
      '#items' => $items,
      '#settings' => $settings,
    ];

    return $elements;
  }

}
