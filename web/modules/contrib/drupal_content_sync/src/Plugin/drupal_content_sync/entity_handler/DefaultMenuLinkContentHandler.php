<?php

namespace Drupal\drupal_content_sync\Plugin\drupal_content_sync\entity_handler;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\drupal_content_sync\ExportIntent;
use Drupal\drupal_content_sync\ImportIntent;
use Drupal\drupal_content_sync\SyncIntent;
use Drupal\drupal_content_sync\Entity\MetaInformation;
use Drupal\drupal_content_sync\Plugin\EntityHandlerBase;

/**
 * Class DefaultMenuLinkContentHandler, providing a minimalistic implementation
 * for menu items, making sure they're referenced correctly by UUID.
 *
 * @EntityHandler(
 *   id = "drupal_content_sync_default_menu_link_content_handler",
 *   label = @Translation("Default Menu Link Content"),
 *   weight = 100
 * )
 *
 * @package Drupal\drupal_content_sync\Plugin\drupal_content_sync\entity_handler
 */
class DefaultMenuLinkContentHandler extends EntityHandlerBase {

  /**
   * @inheritdoc
   */
  public static function supports($entity_type, $bundle) {
    return $entity_type == 'menu_link_content';
  }

  /**
   * @inheritdoc
   */
  public function getAllowedPreviewOptions() {
    return [
      'table' => 'Table',
    ];
  }

  /**
   * @inheritdoc
   */
  public function updateEntityTypeDefinition(&$definition) {
    parent::updateEntityTypeDefinition($definition);

    $module_handler = \Drupal::service('module_handler');
    if ($module_handler->moduleExists('menu_token')) {
      $definition['new_properties']['menu_token_options'] = [
        'type' => 'object',
        'default_value' => NULL,
        'multiple' => FALSE,
      ];
      $definition['new_property_lists']['details']['menu_token_options'] = 'value';
      $definition['new_property_lists']['database']['menu_token_options'] = 'value';
      $definition['new_property_lists']['modifiable']['menu_token_options'] = 'value';
    }
  }

  /**
   * @inheritdoc
   */
  public function getHandlerSettings() {
    $menus = menu_ui_get_menus();
    return [
      'ignore_unpublished' => [
        '#type' => 'checkbox',
        '#title' => 'Ignore disabled',
        '#default_value' => isset($this->settings['handler_settings']['ignore_unpublished']) && $this->settings['handler_settings']['ignore_unpublished'] === 0 ? 0 : 1,
      ],
      'restrict_menus' => [
        '#type' => 'checkboxes',
        '#title' => 'Restrict to menus',
        '#default_value' => isset($this->settings['handler_settings']['restrict_menus']) ? $this->settings['handler_settings']['restrict_menus'] : [],
        '#options' => $menus,
      ],
    ];
  }

  /**
   * @inheritdoc
   */
  public function export(ExportIntent $intent, FieldableEntityInterface $entity = NULL) {
    $result = parent::export($intent, $entity);

    if ($result && $intent->getAction() != SyncIntent::ACTION_DELETE) {
      $module_handler = \Drupal::service('module_handler');
      if ($module_handler->moduleExists('menu_token')) {
        $uuid = $intent->getUuid();
        $config_menu = \Drupal::entityTypeManager()
          ->getStorage('link_configuration_storage')
          ->load($uuid);
        if (!empty($config_menu)) {
          $config_array = unserialize($config_menu->get('configurationSerialized'));
          $intent->setField('menu_token_options', $config_array);
        }
      }
    }

    return $result;
  }

  /**
   * @inheritdoc
   */
  protected function setEntityValues(ImportIntent $intent, FieldableEntityInterface $entity = NULL) {
    $result = parent::setEntityValues($intent, $entity);

    if ($intent->getAction() != SyncIntent::ACTION_DELETE) {
      $module_handler = \Drupal::service('module_handler');
      if ($module_handler->moduleExists('menu_token')) {
        $config_array = $intent->getField('menu_token_options');
        if (!empty($config_array)) {
          $uuid = $intent->getUuid();
          $config_menu = \Drupal::entityTypeManager()
            ->getStorage('link_configuration_storage')
            ->load($uuid);
          if (empty($config_menu)) {
            $config_menu = \Drupal::entityTypeManager()
              ->getStorage('link_configuration_storage')
              ->create([
                'id' => $uuid,
                'label' => 'Menu token link configuration',
                'linkid' => (string) $intent->getField('link')[0]['uri'],
                'configurationSerialized' => serialize($config_array),
              ]);
          }
          else {
            $config_menu->set("linkid", (string) $intent->getField('link')[0]['uri']);
            $config_menu->set("configurationSerialized", serialize($config_array));
          }
          $config_menu->save();
        }
      }
    }

    return $result;
  }

  /**
   * @inheritdoc
   */
  public function ignoreImport(ImportIntent $intent) {
    $action = $intent->getAction();
    if ($action == SyncIntent::ACTION_DELETE) {
      return parent::ignoreImport($intent);
    }

    // Not published? Ignore this revision then.
    if ((empty($intent->getField('enabled')) || !$intent->getField('enabled')[0]['value']) && $this->settings['handler_settings']['ignore_unpublished']) {
      // Unless it's a delete, then it won't have a status and is independent
      // of published state, so we don't ignore the import.
      return TRUE;
    }

    if (!empty($this->settings['handler_settings']['restrict_menus'])) {
      $menu = $intent->getField('menu_name')[0]['value'];
      if (empty($this->settings['handler_settings']['restrict_menus'][$menu])) {
        return TRUE;
      }
    }

    $link = $intent->getField('link');
    if (isset($link[0]['uri'])) {
      $uri = $link[0]['uri'];
      preg_match('@^internal:/([a-z_0-9]+)\/([a-z0-9-]+)$@', $uri, $found);
      if (!empty($found)) {
        $referenced = \Drupal::service('entity.repository')
          ->loadEntityByUuid($found[0][1], $found[0][2]);
        if (!$referenced) {
          $intent->setField('enabled', [['value' => 0]]);
        }
      }
    }
    elseif (!empty($link[0][SyncIntent::ENTITY_TYPE_KEY]) && !empty($link[0][SyncIntent::UUID_KEY])) {
      $intent->setField('enabled', [['value' => 0]]);
    }

    return parent::ignoreImport($intent);
  }

  /**
   * @inheritdoc
   */
  public function ignoreExport(ExportIntent $intent) {
    /**
     * @var \Drupal\menu_link_content\Entity\MenuLinkContent $entity
     */
    $entity = $intent->getEntity();

    if (!$entity->isEnabled() && $this->settings['handler_settings']['ignore_unpublished']) {
      return TRUE;
    }

    if (!empty($this->settings['handler_settings']['restrict_menus'])) {
      $menu = $entity->getMenuName();
      if (empty($this->settings['handler_settings']['restrict_menus'][$menu])) {
        return TRUE;
      }
    }

    $uri = $entity->get('link')->getValue()[0]['uri'];
    if (substr($uri, 0, 7) == 'entity:') {
      preg_match('/^entity:(.*)\/(\d*)$/', $uri, $found);
      // This means we're already dealing with a UUID that has not been resolved
      // locally yet. So there's no sense in exporting this back to the pool.
      if (empty($found)) {
        return TRUE;
      }
      else {
        $link_entity_type = $found[1];
        $link_entity_id   = $found[2];
        $entity_manager   = \Drupal::entityTypeManager();
        $reference        = $entity_manager->getStorage($link_entity_type)
          ->load($link_entity_id);
        // Dead reference > ignore.
        if (empty($reference)) {
          return TRUE;
        }

        // Sync not supported > Ignore.
        if (!$this->flow->supportsEntity($reference)) {
          return TRUE;
        }

        $meta_infos = MetaInformation::getInfosForEntity($link_entity_type, $reference->uuid(), ['pool' => $intent->getPool()->id]);
        $exported   = FALSE;
        foreach ($meta_infos as $flow_id => $info) {
          if (!$info->getLastExport()) {
            continue;
          }
          $exported = TRUE;
        }
        if (!$exported && !ExportIntent::isExporting($link_entity_type, $reference->uuid(), $intent->getPool()->id)) {
          return TRUE;
        }
      }
    }

    return parent::ignoreExport($intent);
  }

}
