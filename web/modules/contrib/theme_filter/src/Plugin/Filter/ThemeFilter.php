<?php
/**
 * @file
 * Contains \Drupal\theme_filter\Plugin\Filter\ThemeFilter.
 */
namespace Drupal\theme_filter\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a filter that replaces Theme path tokens with their values.
 *
 * @Filter(
 *   id = "theme_filter",
 *   title = @Translation("Replaces Theme path tokens with their values"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
 *   settings = { }
 * )
 */
class ThemeFilter extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    return new FilterProcessResult($this->filterThemeTokens($text));
  }

  /**
   * Filter process callback for the Theme path to replace.
   */
  public function filterThemeTokens($text) {
    if (strpos($text, '[theme:') !== FALSE) {
      preg_match_all('/\[theme:(?P<themename>\w+)\]/', $text, $matches);

      $trans = array();
      if (!empty($matches)) {
        foreach ($matches[0] as $key => $match) {
          if (empty($matches['absolute'])) {
            $trans[$match] = base_path() . drupal_get_path('theme', $matches['themename'][$key]);
          }
        }
      }

      preg_match_all('/\[theme:(?P<themename>\w+):(?P<absolute>\w+)\]/', $text, $matches);
      if (!empty($matches)) {
        foreach ($matches[0] as $key => $match) {
          if (!empty($matches['absolute'])) {
            $trans[$match] = file_create_url(drupal_get_path('theme', $matches['themename'][$key]));
          }
        }
      }

      $text = strtr($text, $trans);
    }

    if (strpos($text, '[path-to-theme]') !== FALSE) {
      preg_match_all('/\[path-to-theme]/', $text, $matches);

      $trans = array();
      if (!empty($matches)) {
        foreach ($matches[0] as $key => $match) {
          if (empty($matches['absolute'])) {
            $trans[$match] = base_path() . \Drupal::theme()->getActiveTheme()->getPath();
          }
        }
      }

      preg_match_all('/\[path-to-theme:(?P<absolute>\w+)\]/', $text, $matches);
      if (!empty($matches)) {
        foreach ($matches[0] as $key => $match) {
          if (!empty($matches['absolute'])) {
            $trans[$match] = file_create_url(\Drupal::theme()->getActiveTheme()->getPath());
          }
        }
      }

      $text = strtr($text, $trans);
    }

    return $text;
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    return $this->t('Available tokens are [theme:THEME_NAME], [theme:THEME_NAME:absolute],
    [path-to-theme], [path-to-theme:absolute] which converts path as ( [theme:bartik] => /themes/bartik,
    [theme:bartik:absolute] => http://sitename.com/themes/bartik, [path-to-theme] => /themes/bartik,
    [path-to-theme:absolute] => http://sitename.com/themes/bartik ) respectivly');
  }
}
