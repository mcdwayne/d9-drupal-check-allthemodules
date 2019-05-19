<?php

namespace Drupal\tableau_dashboard\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'access_tableau_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "tableau_dashboard_formatter",
 *   label = @Translation("Tableau dashboard Formatter"),
 *   field_types = {
 *     "tableau_dashboard_field"
 *   }
 * )
 */
class TableauFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    // Creating unique class name. It has to be unique so it can tackle multiple
    // fields on the same page.
    $uniqId = uniqid();
    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#theme' => 'tableau_dashboard',
        '#markup' => $item->value,
        '#type' => 'markup',
      ];
      $elements['#attached']['drupalSettings']['tableau_dashboard']['dashboards'][uniqid()] = [
        'containerId' => $uniqId,
        'display' => $item->value,
      ];
    }
    // Loading JS for embedding the dashboard.
    $elements['#attached']['library'][] = 'tableau_dashboard/tableau_dashboard';
    // Loading variables needed to embed the Tableau dashboard successfully.
    $config = \Drupal::config("tableau_dashboard.settings");
    $elements['#attached']['drupalSettings']['tableau_dashboard']['url'] = $config->get('url');
    $elements['#attached']['drupalSettings']['tableau_dashboard']['siteName'] = $config->get('site_name');
    $elements['#type'] = 'container';
    $elements['#prefix'] = '<div class="vizContainer" data-tableau-container="' . $uniqId . '">';
    $elements['#suffix'] = '</div>';
    // Adding some JS API settings.
    $elements['#attached']['drupalSettings']['tableau_dashboard']['hideTabs'] = !$config->get('show_tabs');
    $elements['#attached']['drupalSettings']['tableau_dashboard']['hideToolbar'] = !$config->get('show_toolbar');
    $elements['#attached']['drupalSettings']['tableau_dashboard']['desktop'] = $config->get('desktop_width');
    $elements['#attached']['drupalSettings']['tableau_dashboard']['tablet'] = $config->get('tablet_width');
    $elements['#attached']['drupalSettings']['tableau_dashboard']['mobile'] = $config->get('mobile_width');
    return $elements;
  }
}
