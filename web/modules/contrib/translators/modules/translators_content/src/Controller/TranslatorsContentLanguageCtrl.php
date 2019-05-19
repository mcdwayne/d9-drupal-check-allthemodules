<?php

namespace Drupal\translators_content\Controller;

use Drupal\Core\Language\Language;
use Drupal\node\NodeInterface;

/**
 * Class TranslatorsContentLanguageCtrl.
 *
 * @package Drupal\translators_content\Controller
 */
class TranslatorsContentLanguageCtrl {

  /**
   * List of langcodes that shouldn't be filtered.
   *
   * @var array
   */
  protected static $ignoredLangcodes = [
    Language::LANGCODE_NOT_APPLICABLE,
    Language::LANGCODE_NOT_SPECIFIED,
  ];

  /**
   * After build callback for the langcode element on node forms.
   *
   * @param array $element
   *   Element render-able array.
   *
   * @return array
   *   Updated element.
   */
  public static function nodeFormLangcodeAfterBuild(array $element) {
    // Prevent filtering for "non-restricted" users.
    if (!static::needsFiltering()) {
      return $element;
    }

    static::filter($element[0]['value']['#options']);
    return $element;
  }

  /**
   * Check for user's permissions that might allow to use any languages.
   *
   * @return bool
   *   TRUE - if filtering is needed, FALSE otherwise.
   */
  protected static function needsFiltering() {
    if (\Drupal::currentUser()->hasPermission('administer nodes')) {
      return FALSE;
    }
    $node = \Drupal::routeMatch()->getParameter('node');
    if ($node instanceof NodeInterface) {
      $permission = "create {$node->bundle()} content";
      return !\Drupal::currentUser()->hasPermission($permission);
    }
    return TRUE;
  }

  /**
   * Filter languages list.
   *
   * @param array &$options
   *   Dropdown options array.
   */
  protected static function filter(array &$options) {
    /** @var \Drupal\translators\Services\TranslatorSkills $translatorSkills */
    $translatorSkills = \Drupal::service('translators.skills');
    foreach ($options as $langcode => $label) {
      if (in_array($langcode, static::$ignoredLangcodes)) {
        continue;
      }
      if (!$translatorSkills->hasSkill($langcode)) {
        unset($options[$langcode]);
      }
    }
  }

}
