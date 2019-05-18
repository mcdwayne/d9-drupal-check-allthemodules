<?php
namespace Drupal\fico\Plugin\Field\FieldFormatter\Condition;

use Drupal\fico\Plugin\FieldFormatterConditionBase;

/**
 * The plugin for check empty fields.
 *
 * @FieldFormatterCondition(
 *   id = "hide_on_pages",
 *   label = @Translation("Hide on specific pages"),
 *   dsFields = FALSE,
 *   types = {
 *     "all"
 *   }
 * )
 */
class HideOnPages extends FieldFormatterConditionBase {

  /**
   * {@inheritdoc}
   */
  public function alterForm(&$form, $settings) {
    $default_visibility = isset($settings['settings']['visibility']) ? $settings['settings']['visibility'] : 0;
    $default_pages = isset($settings['settings']['pages']) ? $settings['settings']['pages'] : '';
    $form['visibility'] = array(
      '#title' => t('Type of display'),
      '#type' => 'radios',
      '#options' => array(
        0 => t('All pages except those listed'),
        1 => t('Only the listed pages'),
      ),
      '#default_value' => $default_visibility,
    );

    $form['pages'] = array(
      '#type' => 'textarea',
      '#title' => t('Enter pages'),
      '#cols' => 10,
      '#default_value' => $default_pages,
      '#description' => t("Specify pages by using their paths. Enter one path per line. * is used as wildcard."),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access(&$build, $field, $settings) {
    $path = \Drupal::service('path.current')->getPath();
    $path = ($path[0] == '/') ? substr($path, 1) : $path;
    $page_match = \Drupal::service('path.matcher')->matchPath($path, $settings['settings']['pages']);
    $page_match = !($settings['settings']['visibility'] xor $page_match);
    if ($page_match) {
      $build[$field]['#access'] = FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function summary($settings) {
    $pages = explode("\n", $settings['settings']['pages']);
    return t("Condition: %condition - %visibility (%settings)", [
      "%condition" => t('Hide on specific pages'),
      "%visibility" => $settings['settings']['visibility'] == 1 ? t('Only the listed pages') : t('All pages except those listed'),
      "%settings" => implode(', ', $pages),
    ]);
  }

}
