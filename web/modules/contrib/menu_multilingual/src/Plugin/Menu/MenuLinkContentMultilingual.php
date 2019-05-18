<?php

namespace Drupal\menu_multilingual\Plugin\Menu;

use Drupal\menu_link_content\Plugin\Menu\MenuLinkContent;

/**
 * Provides the menu link plugin for content menu links.
 *
 * The aims of this class is to provide standard multilingual methods.
 */
class MenuLinkContentMultilingual extends MenuLinkContent {

  /**
   * Gets the language of the menu link.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *
   * @return string
   *   The language id.
   */
  public function getLanguage() {
    // We only need to get the title from the actual entity if it may be a
    // translation based on the current language context. This can only happen
    // if the site is configured to be multilingual.
    if ($this->languageManager->isMultilingual()) {
      return $this->getEntity()->get('langcode')->value;
    }
    return $this->languageManager->getDefaultLanguage()->getId();
  }

  /**
   * Retrieve the languages from translations of the menu link.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *
   * @return string
   *   The language ids.
   */
  public function getTranslationLanguages() {
    return $this->getEntity()->getTranslationLanguages();
  }

}
