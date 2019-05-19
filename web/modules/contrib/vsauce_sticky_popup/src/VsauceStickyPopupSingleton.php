<?php

namespace Drupal\vsauce_sticky_popup;

/**
 * Class VsauceStickyPopupSingleton.
 *
 * @package Drupal\vsauce_sticky_popup
 *   Store data in singleton.
 */
class VsauceStickyPopupSingleton {

  /**
   * {@inheritdoc}
   */
  private static $vsauceStickyPopupInstance;

  private $items;

  /**
   * Initialize Singleton Instance.
   *
   * @return VsauceStickyPopupSingleton
   *   The Instance.
   */
  public static function getInstance() {
    if (!isset(self::$vsauceStickyPopupInstance)) {
      self::$vsauceStickyPopupInstance = new self();
    }
    return self::$vsauceStickyPopupInstance;
  }

  /**
   * Add new item to VSP.
   *
   * @param array $item
   *   The item with data.
   *
   * @return bool
   *   Boolean state of insert.
   */
  public function addItem(array $item) {

    // Position Sticky Popup.
    if (isset($item['position_sticky_popup']) && !empty($item['position_sticky_popup'])) {
      $position_sticky_popup = $item['position_sticky_popup'];
      $this->items[$position_sticky_popup]['collapse'] = $item['collapse'];
    }
    else {
      return FALSE;
    }

    // Position action wrapper.
    if (isset($item['action_wrapper']) && !empty($item['action_wrapper'])) {

      if (isset($item['action_wrapper']['position_open_button'])) {
        $this->items[$position_sticky_popup]['action_wrapper']['position_open_button'] = $item['action_wrapper']['position_open_button'];
      }

      if (isset($item['action_wrapper']['position_arrow'])) {
        $this->items[$position_sticky_popup]['action_wrapper']['position_arrow'] = $item['action_wrapper']['position_arrow'];
      }

      if (!isset($this->items[$position_sticky_popup]['action_wrapper']['tab_label']) && empty($this->items[$position_sticky_popup]['action_wrapper']['tab_label'])) {

        $this->items[$position_sticky_popup]['action_wrapper']['tab_label'] = $item['action_wrapper']['tab_label'];
      }
      else {
        $current = $this->items[$position_sticky_popup]['action_wrapper']['tab_label'];
        if (!empty($item['action_wrapper']['tab_label'])) {
          $this->items[$position_sticky_popup]['action_wrapper']['tab_label'] = $current . ' ' . $item['action_wrapper']['tab_label'];
        }
      }
    }

    // Content sticky popup.
    if (isset($item['content']['content'])) {
      if (isset($item['content']['id']) && !empty($item['content']['id'])) {
        $id = 'vsp-id-' . $position_sticky_popup . '-' . $item['content']['id'];
      }
      else {
        $id = 'vsp-id-' . $position_sticky_popup . '-' . uniqid();
      }

      if (isset($item['content']['content'])) {
        $this->items[$position_sticky_popup]['content'][$id] = $item['content']['content'];
      }
    }
  }

  /**
   * The items on VSP.
   *
   * @return mixed
   *   Array with items.
   */
  public function getItems() {
    return $this->items;
  }

  /**
   * The basic structure of empty item to generate VSP.
   *
   * @return array
   *   Empty array.
   */
  public function getEmptyItem() {
    return [
      'position_sticky_popup' => '',
      'collapse' => '',
      'action_wrapper' => [
        'position_open_button' => '',
        'position_arrow' => '',
        'tab_label' => '',
      ],
      'content' => [
        'id' => '',
        'content' => '',
      ],
    ];
  }

}
