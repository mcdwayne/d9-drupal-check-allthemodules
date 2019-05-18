<?php

namespace Drupal\menu_multilingual\Menu;

use Drupal\menu_link_content\Plugin\Menu\MenuLinkContent;
use Drupal\views\Plugin\Menu\ViewsMenuLink;

/**
 * Class MenuMultilingualLinkTreeModifier.
 *
 * Used to filter out menu items.
 */
class MenuMultilingualLinkTreeModifier {

  /**
   * MenuMultilingualLinkTreeModifier constructor.
   *
   * @param bool $allow_labels
   *   The allow_label filter flag.
   * @param bool $allow_content
   *   The allow_content filter flag.
   */
  public function __construct($allow_labels = FALSE, $allow_content = FALSE) {
    $this->filter_labels = $allow_labels;
    $this->filter_content = $allow_content;
  }

  /**
   * Pass menu links from render array of the block to the filter method.
   *
   * @param array $build
   *   The block render-able array.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *
   * @return array
   *   The modified render-able array.
   */
  public function filterLinksInRenderArray(array $build) {
    $tree =& $build['content']['#items'];
    if (!is_array($tree)) {
      return $build;
    }
    $tree = $this->filtersLinks($tree);
    // Hide block if there are no menu items.
    if (empty($tree)) {
      $build = [
        '#markup' => '',
        '#cache'  => $build['#cache'],
      ];
    }
    return $build;
  }

  /**
   * Filter wrapper for either links or menu link tree.
   *
   * @param array $tree
   *   The already built menu tree.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *
   * @return array
   *   The new menu tree.
   */
  public function filtersLinks(array $tree) {
    $new_tree = [];
    foreach ($tree as $key => $v) {
      if ($tree[$key]['below']) {
        $tree[$key]['below'] = $this->filtersLinks($tree[$key]['below']);
      }
      $link = $tree[$key]['original_link'];
      if ($this->hasTranslationOrIsDefaultLang($link)) {
        $new_tree[$key] = $tree[$key];
      }
    }
    return $new_tree;
  }

  /**
   * Check link for translation or current language.
   *
   * @param mixed $link
   *   The menu link plugin instance.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *
   * @return bool
   *   True if link pass a multilingual options.
   */
  protected function hasTranslationOrIsDefaultLang($link) {
    $current_lang = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $result = FALSE;
    $has_translated_label = FALSE;
    $has_translated_content = FALSE;

    if ($this->filter_labels) {
      $has_translated_label = $this->linkIsTranslated($link, $current_lang);
    }
    if ($this->filter_content) {
      $has_translated_content = $this->linkedEntityHasTranslationsOrIsDefault($link, $current_lang);
    }

    if ($this->filter_labels && $this->filter_content) {
      if ($has_translated_label && $has_translated_content) {
        $result = TRUE;
      }
    }
    else {
      if ($this->filter_labels) {
        $result = $has_translated_label;
      }
      elseif ($this->filter_content) {
        $result = $has_translated_content;
      }
    }

    return $result;
  }

  /**
   * Check link for translations or current language.
   *
   * @param mixed $link
   *   The link that will be checked.
   * @param string $lang
   *   The language id.
   *
   * @return bool
   *   True if link pass a multilingual options.
   */
  private function linkIsTranslated($link, $lang) {
    $result = FALSE;

    $callbacks = [
      'isTranslatedMenuLinkContentMultilingual',
      'isTranslatedViewLink',
    ];

    foreach ($callbacks as $condition) {
      $check = call_user_func([self::class, $condition], $link, $lang);
      if ($check === NULL) {
        continue;
      }
      $result = $check;
      break;
    }

    return $result;
  }

  /**
   * Check menu item link for translations or current language.
   *
   * @param mixed $link
   *   The link that will be checked.
   * @param string $lang
   *   The language id.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *
   * @return bool
   *   True if link pass a multilingual options.
   */
  private function linkedEntityHasTranslationsOrIsDefault($link, $lang) {
    if (empty($link->getRouteName()) || strpos($link->getRouteName(), 'entity.') === FALSE) {
      return TRUE;
    }

    $type   = current(array_keys($link->getRouteParameters()));
    $id     = $link->getRouteParameters()[$type];
    $result = FALSE;

    if (empty($type) || empty($id)) {
      return $result;
    }

    /* @var \Drupal\Core\Entity\ContentEntityBase $entity */
    $entity = \Drupal::entityTypeManager()
      ->getStorage($type)
      ->load($id);

    if ($lang == $entity->get('langcode')) {
      $result = TRUE;
    }
    elseif ($this->entityHasTranslation($entity, $lang)) {
      $result = TRUE;
    }
    return $result;
  }

  /**
   * Helper method to check if entity is translateable.
   *
   * @param \Drupal\menu_multilingual\Plugin\Menu\MenuLinkContentMultilingual|\Drupal\Core\Entity\ContentEntityBase $entity
   *   The base entity object or menu link plugin to get translations on.
   * @param string $lang
   *   The language id.
   *
   * @return bool
   *   Return true when language matches translations languages,
   *   or non translatable.
   */
  private function entityHasTranslation($entity, $lang) {
    // Return false for "Not Specified" language (langcode 'und').
    if ($entity->language()->getId() == 'und') {
      return FALSE;
    }
    // Return true for non-translatable entities and
    // entity with "Not applicable" language (langcode 'zxx').
    elseif (!method_exists($entity, 'isTranslatable') || $entity->language()->getId() == 'zxx') {
      return TRUE;
    }
    $translation_codes = array_keys($entity->getTranslationLanguages());
    return in_array($lang, $translation_codes);
  }

  /**
   * Check if link is ViewsMenuLink & translated.
   *
   * @param mixed $link
   *   The link that will be checked.
   * @param string $lang
   *   The language id.
   *
   * @return bool
   *   True if link is ViewsMenuLink and has translation.
   */
  private function isTranslatedViewLink($link, $lang) {
    $result = FALSE;
    if (!($link instanceof ViewsMenuLink)) {
      return NULL;
    }

    $view_id = sprintf('views.view.%s', $link->getMetaData()['view_id']);
    $original = \Drupal::config($view_id)->get('langcode');

    // Make sure that original configuration exists for given view.
    if (!$original || $lang === $original) {
      $result = TRUE;
    }
    // ConfigurableLanguageManager::getLnguageConfigOverride() always
    // returns a new configuration override for the original language.
    else {
      /** @var \Drupal\language\Config\LanguageConfigOverride $config */
      $config = \Drupal::languageManager()->getLanguageConfigOverride($lang, $view_id);
      // Configuration override will be marked as a new if one does not
      // exist for current language (thus has no translation).
      $result = $config->isNew() ? FALSE : TRUE;
    }
    return $result;

  }

  /**
   * Check if link is MenuLinkContent & translated.
   *
   * @param mixed $link
   *   The link that will be checked.
   * @param string $lang
   *   The language id.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *
   * @return bool
   *   True if link is MenuLinkContent and has translation.
   */
  private function isTranslatedMenuLinkContentMultilingual($link, $lang) {
    $result = FALSE;
    if (!($link instanceof MenuLinkContent)) {
      return NULL;
    }
    $storage = \Drupal::entityTypeManager()->getStorage('menu_link_content');
    if (!empty($link->getPluginDefinition()['metadata']['entity_id'])) {
      $entity_id = $link->getPluginDefinition()['metadata']['entity_id'];
      $entity = $storage->load($entity_id);
      $langcode_key = $entity->getEntityType()->getKey('langcode');
      if ($lang == $entity->get($langcode_key)->value) {
        $result = TRUE;
      }
      elseif ($this->entityHasTranslation($entity, $lang)) {
        $result = TRUE;
      }
    }
    return $result;
  }

}
