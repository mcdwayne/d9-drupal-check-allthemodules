<?php

namespace Drupal\ckeditor_standalone_styles\Plugin\Filter;

use Drupal\filter\Plugin\Filter\FilterHtml;
use Drupal\ckeditor_standalone_styles\Form\CkeditorStandaloneStylesSettingsForm;

/**
 * Class FilterHtmlCustom.
 *
 * Extend core's FilterHtml text filter to dynamically add to the list of
 * allowed HTML based on configured set of custom CKEditor styles.
 *
 * This does two important things:
 * 1) Prevents the text filter from stripping out the CSS classes of the
 *    custom styles when the text is output.
 * 2) Because Drupal synchronizes the allowed HTML from the text filters with
 *    CKEditor's Advanced Content Filter settings, this prevents CKEditor from
 *    stripping out the CSS classes of the custom styles.
 */
class FilterHtmlCustom extends FilterHtml {

  /**
   * {@inheritdoc}
   */
  public function getHTMLRestrictions() {
    $restrictions = parent::getHTMLRestrictions();

    // Couldn't get dependency injection to work correctly, so using global
    // container to get settings.
    $rawStyles = \Drupal::configFactory()->get('ckeditor_standalone_styles.settings')->get('styles');
    $parsedStyles = CkeditorStandaloneStylesSettingsForm::generateStylesSetSetting($rawStyles);
    if ($parsedStyles && !empty($parsedStyles)) {
      foreach ($parsedStyles as $parsedStyle) {
        $element = $parsedStyle['element'];
        $classes = explode(' ', $parsedStyle['attributes']['class']);

        // We require that the element the style is attached to must already
        // be defined as an allowed element in the filter. This prevents
        // style definitions from adding additional elements that we never
        // intended to be added.
        if (!isset($restrictions['allowed'][$element])) {
          continue;
        }

        // The 'classes' element will either not exist at all,
        // exist and be an array of classes that are already allowed, or
        // exist and have a value of TRUE (indicating all classes are already
        // being allowed).
        // If it's set to TRUE, then skip, since there's nothing to do.
        if (isset($restrictions['allowed'][$element]['class']) && $restrictions['allowed'][$element]['class'] === TRUE) {
          continue;
        }

        // If it's not set at all, initialize it to an empty array.
        if (!isset($restrictions['allowed'][$element]['class'])) {
          $restrictions['allowed'][$element]['class'] = [];
        }

        // Now we can add each class from the parsed style.
        foreach ($classes as $class) {
          $restrictions['allowed'][$element]['class'][$class] = TRUE;
        }
      }
    }

    return $restrictions;
  }

}
