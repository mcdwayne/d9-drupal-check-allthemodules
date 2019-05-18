<?php

use Drupal\Core\Render\BubbleableMetadata;


/**
 * Implements hook_token_info().
 */
function commerce_product_menu_ui_token_info()
{
  // Menu tokens.
  $info['tokens']['commerce_product']['menu-link'] = [
    'name' => t('Menu link'),
    'description' => t("The menu link for this commerce product."),
    'type' => 'menu-link',
  ];
  return $info;
}

/**
 * Implements hook_tokens().
 */
function commerce_product_menu_ui_tokens($type, $tokens, array $data = [], array $options = [], BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];

  /** @var \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager */
  $menu_link_manager = \Drupal::service('plugin.manager.menu.link');

  $url_options = ['absolute' => TRUE];
  if (isset($options['langcode'])) {
    $url_options['language'] = \Drupal::languageManager()->getLanguage($options['langcode']);
    $langcode = $options['langcode'];
  }
  else {
    $langcode = NULL;
  }

  // Commerce product tokens.  
  if ($type == 'commerce_product' && !empty($data['commerce_product'])) {
    /** @var \Drupal\commerce_product\Entity\ProductInterface $commerce_product */
    $commerce_product = $data['commerce_product'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'menu-link':
          // On product-form save we populate a calculated field with a menu_link
          // references.
          // @see token_node_menu_link_submit()
          if ($commerce_product->getFieldDefinition('menu_link') && $menu_link = $commerce_product->menu_link->entity) {
            /** @var \Drupal\menu_link_content\MenuLinkContentInterface $menu_link */
            $replacements[$original] = $menu_link->getTitle();
          }
          else {
            $url = $commerce_product->toUrl();
            if ($links = $menu_link_manager->loadLinksByRoute($url->getRouteName(), $url->getRouteParameters())) {
              $link = _commerce_product_menu_ui_token_link_best_match($commerce_product, $links);
              $replacements[$original] = token_menu_link_translated_title($link, $langcode);
            }
          }
          break;
      }

      // Chained token relationships.
      if ($menu_tokens = \Drupal::token()->findWithPrefix($tokens, 'menu-link')) {
        if ($commerce_product->getFieldDefinition('menu_link') && $menu_link = $commerce_product->menu_link->entity) {
          /** @var \Drupal\menu_link_content\MenuLinkContentInterface $menu_link */
          $replacements += \Drupal::token()->generate('menu-link', $menu_tokens, ['menu-link' => $menu_link], $options, $bubbleable_metadata);
        }
        else {
          $url = $commerce_product->toUrl();
          if ($links = $menu_link_manager->loadLinksByRoute($url->getRouteName(), $url->getRouteParameters())) {
            $link = _commerce_product_menu_ui_token_link_best_match($commerce_product, $links);
            $replacements += \Drupal::token()->generate('menu-link', $menu_tokens, ['menu-link' => $link], $options, $bubbleable_metadata);
          }
        }
      }
    }
  }

  // Menu link tokens.
  if ($type == 'menu-link' && !empty($data['menu-link'])) {
    /** @var \Drupal\Core\Menu\MenuLinkInterface $link */
    $link = $data['menu-link'];

    if ($link instanceof MenuLinkContentInterface) {
      $link = $menu_link_manager->createInstance($link->getPluginId());
    }

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'menu':
          if ($menu = Menu::load($link->getMenuName())) {
            $replacements[$original] = $menu->label();
          }
          break;

        case 'edit-url':
          $replacements[$original] = $link->getEditRoute()->setOptions($url_options)->toString();
          break;
      }
    }

    // Chained token relationships.
    if (($menu_tokens = \Drupal::token()->findWithPrefix($tokens, 'menu')) && $menu = Menu::load($link->getMenuName())) {
      $replacements += \Drupal::token()->generate('menu', $menu_tokens, ['menu' => $menu], $options, $bubbleable_metadata);
    }
  }

  // Menu tokens.
  if ($type == 'menu' && !empty($data['menu'])) {
    /** @var \Drupal\system\MenuInterface $menu */
    $menu = $data['menu'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'name':
          $replacements[$original] = $menu->label();
          break;

        case 'machine-name':
          $replacements[$original] = $menu->id();
          break;

        case 'description':
          $replacements[$original] = $menu->getDescription();
          break;

        case 'menu-link-count':
          $replacements[$original] = $menu_link_manager->countMenuLinks($menu->id());
          break;

        case 'edit-url':
          $replacements[$original] = Url::fromRoute('entity.menu.edit_form', ['menu' => $menu->id()], $url_options)->toString();
          break;
      }
    }
  }

  return $replacements;
}

/**
 * Returns a best matched link for a given commerce product.
 *
 * If the url exists in multiple menus, default to the one set on the commerce product
 * itself.
 *
 * @param \Drupal\commerce_product\Entity\Product $commerce_product
 *   The commerce product to look up the default menu settings from.
 * @param array $links
 *   An array of instances keyed by plugin ID.
 *
 * @return \Drupal\Core\Menu\MenuLinkInterface
 *   A Link instance.
 */
function _commerce_product_menu_ui_token_link_best_match(\Drupal\commerce_product\Entity\Product $commerce_product, array $links)
{
  // Get the menu ui defaults so we can determine what menu was
  // selected for this commerce product. This ensures that if the commerce product was added
  // to the menu via the commerce product UI, we use that as a default. If it
  // was not added via the commerce product UI then grab the first in the
  // retrieved array.
  $defaults = commerce_product_menu_ui_get_menu_link_defaults($commerce_product);
  if (isset($defaults['id']) && isset($links[$defaults['id']])) {
    $link = $links[$defaults['id']];
  } else {
    $link = reset($links);
  }
  return $link;
}