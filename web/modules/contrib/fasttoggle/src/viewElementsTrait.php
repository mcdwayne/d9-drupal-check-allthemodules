<?php

namespace Drupal\fasttoggle;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Component\Utility\Html;
use Drupal\fasttoggle\Controller\FasttoggleController;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Cache\Cache;

/**
 * Supply shared method for getting elements render array.
 */
trait viewElementsTrait {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    // Get the elements without Fasttoggle links applied.
    $elements = parent::viewElements($items, $langcode);

    $parent = $items->getParent()->getValue();
    $object_type = $parent->getEntityTypeId();
    $object_id = $parent->id();

    $controller = new FasttoggleController();
    $objectManager = $controller->getObjectManager($parent);

    if (!$objectManager) {
      return $elements;
    }

    // Calculate an access result and add cache tags to $elements.
    $accessible = $objectManager->mayEditEntity();

    if ($accessible->isForbidden()) {
      return $elements;
    }

    $renderer = \Drupal::service('renderer');
    $renderer->addCacheableDependency($elements, $accessible);

    foreach ($items as $delta => $item) {
      $linkText = $elements[$delta]['#markup'];

      // Get the group & setting and check access.
      $instance = $item->getFieldDefinition();
      list($groupPlugin, $settingPlugin) = $controller->groupAndSettingFromFieldName($instance);
      if (!$groupPlugin || !$settingPlugin) {
        drupal_set_message("Fasttoggle plugin definition for '{$instance->getName()}' field not found.", "error");
        continue;
      }

      $settingPlugin->setObject($parent);

      if (!$settingPlugin->mayEdit()->isAllowed()) {
        continue;
      }

      $cid = Crypt::randomBytesBase64();
      $group = $groupPlugin->getPluginId();
      $setting = $settingPlugin->getPluginId();

      // Cache using the token generated as the key.
      $cached_fieldview_config = [
        'object_type' => $object_type,
        'object_id' => $object_id,
        'group' => $group,
        'setting' => $setting,
        'token' => $cid,
        'plugin_definition' => $items->getPluginDefinition(),
        'field_definition' => $items->getFieldDefinition(),
        'formatter_settings' => [
          'format' => $this->getSetting('format'),
          'format_custom_true' => $this->getSetting('format_custom_true'),
          'format_custom_false' => $this->getSetting('format_custom_false'),
        ],
        'label' => $this->label,
        'view_mode' => $this->viewMode,
        'third_party_settings' => $this->thirdPartySettings,
        'langcode' => $langcode,
      ];

      \Drupal::cache()->set('fasttoggle-' . $cid,
        $cached_fieldview_config, Cache::PERMANENT,
        ['fasttoggle', "{$object_type}_{$object_id}"]);

      $url_args = [
        'token' => $cid,
      ];

      $elements[$delta] = $settingPlugin->render_array($cid, $linkText,
        $url_args);
    }
    return $elements;
  }
}