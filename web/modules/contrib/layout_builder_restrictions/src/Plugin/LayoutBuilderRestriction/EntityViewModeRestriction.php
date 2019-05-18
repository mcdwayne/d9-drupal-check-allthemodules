<?php

namespace Drupal\layout_builder_restrictions\Plugin\LayoutBuilderRestriction;

use Drupal\Core\Config\Entity\ThirdPartySettingsInterface;
use Drupal\layout_builder_restrictions\Plugin\LayoutBuilderRestrictionBase;
use Drupal\layout_builder\OverridesSectionStorageInterface;
use Drupal\layout_builder\SectionStorageInterface;

/**
 * EntityViewModeRestriction Plugin.
 *
 * @LayoutBuilderRestriction(
 *   id = "entity_view_mode_restriction",
 *   title = @Translation("Restrict blocks/layouts per entity view mode")
 * )
 */
class EntityViewModeRestriction extends LayoutBuilderRestrictionBase {

  /**
   * {@inheritdoc}
   */
  public function alterBlockDefinitions(array $definitions, array $context) {
    // Respect restrictions on allowed blocks specified by the section storage.
    if (isset($context['section_storage'])) {
      $default = $context['section_storage'] instanceof OverridesSectionStorageInterface ? $context['section_storage']->getDefaultSectionStorage() : $context['section_storage'];
      if ($default instanceof ThirdPartySettingsInterface) {
        $third_party_settings = $default->getThirdPartySetting('layout_builder_restrictions', 'entity_view_mode_restriction', []);
        $allowed_blocks = (isset($third_party_settings['allowed_blocks'])) ? $third_party_settings['allowed_blocks'] : [];
      }
      else {
        $allowed_blocks = [];
      }
      // Filter blocks from entity-specific SectionStorage (i.e., UI).
      if (!empty($allowed_blocks)) {
        foreach ($definitions as $delta => $definition) {
          $category = (string) $definition['category'];
          if (in_array($category, array_keys($allowed_blocks))) {
            // This category has restrictions.
            if (!in_array($delta, $allowed_blocks[$category])) {
              // The current block is not in the allowed list for this category.
              unset($definitions[$delta]);
            }
          }
        }
      }
    }
    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function alterSectionDefinitions(array $definitions, array $context) {
    // Respect restrictions on allowed layouts specified by section storage.
    if (isset($context['section_storage'])) {
      $default = $context['section_storage'] instanceof OverridesSectionStorageInterface ? $context['section_storage']->getDefaultSectionStorage() : $context['section_storage'];
      if ($default instanceof ThirdPartySettingsInterface) {
        $third_party_settings = $default->getThirdPartySetting('layout_builder_restrictions', 'entity_view_mode_restriction', []);
        $allowed_layouts = (isset($third_party_settings['allowed_layouts'])) ? $third_party_settings['allowed_layouts'] : [];
        // Filter blocks from entity-specific SectionStorage (i.e., UI).
        if (!empty($allowed_layouts)) {
          $definitions = array_intersect_key($definitions, array_flip($allowed_layouts));
        }
      }
    }
    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function blockAllowedinContext(SectionStorageInterface $section_storage, $delta_from, $delta_to, $region_to, $block_uuid, $preceding_block_uuid = NULL) {
    $contexts = $section_storage->getContexts();
    if ($section_storage instanceof OverridesSectionStorageInterface) {
      $entity = $contexts['entity']->getContextValue();
      $view_mode = $contexts['view_mode']->getContextValue();
      $entity_type = $entity->getEntityTypeId();
      $bundle = $entity->bundle();
    }
    else {
      $entity = $contexts['display']->getContextValue();
      $view_mode = $entity->getMode();
      $bundle = $entity->getTargetBundle();
      $entity_type = $entity->getTargetEntityTypeId();
    }
    // Get "from" section and layout id. (not needed?)
    $section_from = $section_storage->getSection($delta_from);
    $layout_id_from = $section_from->getLayoutId();

    // Get "to" section and layout id.
    $section_to = $section_storage->getSection($delta_to);
    $layout_id_to = $section_to->getLayoutId();

    // Get block information.
    $component = $section_from->getComponent($block_uuid)->toArray();
    $block_id = $component['configuration']['id'];
    $context = $entity_type . "." . $bundle . "." . $view_mode;
    $storage = \Drupal::entityTypeManager()->getStorage('entity_view_display');
    $view_display = $storage->load($context);
    $third_party_settings = $view_display->getThirdPartySetting('layout_builder_restrictions', 'entity_view_mode_restriction', []);
    $allowed_blocks = (isset($third_party_settings['allowed_blocks'])) ? $third_party_settings['allowed_blocks'] : [];
    $block_id_parts = explode(':', $block_id);
    $has_restrictions = FALSE;
    if (!empty($allowed_blocks)) {
      $has_restrictions = TRUE;
      foreach ($allowed_blocks as $category => $items) {
        if (isset($items[0])) {
          $parts = explode(':', $items[0]);
          if ($parts[0] == $block_id_parts[0]) {
            foreach ($items as $item) {
              if ($item == $block_id) {
                $has_restrictions = FALSE;
                break;
              }
            }
          }
        }
      }
    }
    if ($has_restrictions) {
      return t("There is a restriction on %block placement in the %layout %region region for %type content.", [
        "%block" => end($block_id_parts),
        "%layout" => $layout_id_to,
        "%region" => $region_to,
        "%type" => $bundle,
      ]);
    }

    // Default: this block is not restricted.
    return TRUE;
  }

}
