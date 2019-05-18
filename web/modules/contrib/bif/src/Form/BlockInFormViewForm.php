<?php
/**
 * @file
 */

namespace Drupal\block_in_form\Form;

use Drupal\block\Entity\Block;
use Drupal\block_content\Entity\BlockContent;
use Drupal\block_in_form\BlockInFormCommon;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Render\Element;

class BlockInFormViewForm {

  use BlockInFormCommon;

  /**
   * Attach groups to the (form) build.
   *
   * @param Array $element
   *   The part of the form.
   *  @param Array $context
   *   The contextual information.
   */
  public function attachBlocks(&$element, $context) {
    $entity_type = $context['entity_type'];
    $bundle = $context['bundle'];
    $mode = $context['mode'];
    $display_context = $context['display_context'];

    $element['#blocksinform'] = $this->infoBlocks($entity_type, $bundle, $display_context, $mode);

    // Create a lookup array.
    $block_children = [];
    foreach ($element['#blocksinform'] as $block_name => $block) {
      foreach ($block->children as $child) {
        $block_children[$child] = $block_name;
      }
    }
    $element['#block_children'] = $block_children;
    $element['#entity_type'] = $entity_type;
  }

  /**
   * Pre render callback for rendering groups.
   * @see field_group_field_attach_form
   * @param $element Form that is being rendered.
   */
  public function formPreRender($element) {
    if (empty($element['#block_in_form_pre_render'])) {
      $element['#block_in_form_pre_render'] = TRUE;
      // No groups on the entity.
      if (empty($element['#blocksinform'])) {
        return $element;
      }
      $this->buildEntityBlocks($element, 'form');
    }

    return $element;
  }

  /**
   * Function to pre render the field group element.
   *
   * @see field_group_fields_nest()
   *
   * @param $element
   *   Render array of group element that needs to be created.
   * @param $group
   *   Object with the group information.
   * @param $rendering_object
   *   The entity / form beïng rendered.
   */
  private function blockPreRender(&$element, $block, &$rendering_object) {
    $this->blockInFormPreRender($element, $block, $rendering_object);

    // Let modules define their wrapping element.
    // Note that the group element has no properties, only elements.
    foreach (\Drupal::moduleHandler()->getImplementations('block_in_form_pre_render') as $module) {
      // The intention here is to have the opportunity to alter the
      // elements, as defined in hook_field_group_formatter_info.
      // Note, implement $element by reference!
      $function = $module . '_block_in_form_pre_render';
      $function($element, $block, $rendering_object);
    }

    // Allow others to alter the pre_render.
    \Drupal::moduleHandler()->alter('block_in_form_pre_render', $element, $block, $rendering_object);
  }

  /**
   * Implements hook_field_group_pre_render().
   *
   * @param Array $element
   *   Group beïng rendered.
   * @param Object $group
   *   The Field group info.
   * @param $rendering_object
   *   The entity / form beïng rendered
   */
  private function blockInFormPreRender(&$element, &$block, &$rendering_object) {
    // Add all field_group format types to the js settings.
    $element['#attached']['drupalSettings']['block_in_form'] = [
      $block->block_name => [
        'mode' => $block->mode,
        'context' => $block->context,
        'settings' => $block->block_settings,
      ],
    ];

    $element['#weight'] = $block->weight;

    $blockManager = \Drupal::service('plugin.manager.block');
    $plugin_block = $blockManager->createInstance($block->plugin_id, $block->block_settings);
    $access_result = $plugin_block->access(\Drupal::currentUser());
    $content = $plugin_block->build();
    $plugin_id = explode(':', $block->plugin_id);

    if(strstr($block->plugin_id, 'views') && !empty($block->block_settings['views_label'])) {
      $content['#title'] = ['#markup' => $block->block_settings['views_label'], '#allowed_tags' => Xss::getHtmlTagList()];
    }

    $element['#theme'] = 'block';
    $element['#configuration'] = $block->block_settings;
    $element['#id'] = $block->block_name ? $block->block_name : $block->plugin_id;
    $element['#plugin_id'] = $block->plugin_id;
    $element['#base_plugin_id'] = isset($plugin_id[0]) ? $plugin_id[0] : NULL;
    $element['#derivative_plugin_id'] = isset($plugin_id[1]) ? $plugin_id[1] : NULL;
    $element['#attributes'] = ["class" => [$block->block_name]];
    $element['content'] = [];

    if ($access_result === TRUE || (is_object($access_result) && !$access_result->isForbidden())) {
      if ($content !== NULL && !Element::isEmpty($content)) {
        foreach (['#attributes', '#contextual_links'] as $property) {
          if (isset($content[$property]) && is_array($content[$property])) {
            if (isset($element[$property])) {
              $element[$property] += $content[$property];
            }
            else {
              $element[$property] = $content[$property];
            }
            unset($content[$property]);
          }
        }
        $element['content'] = $content;
      }
      else {
        $element = [
          '#markup' => '',
          '#cache' => $element['#cache'],
        ];
        if (!empty($content)) {
          CacheableMetadata::createFromRenderArray($element)
            ->merge(CacheableMetadata::createFromRenderArray($content))
            ->applyTo($element);
        }
      }
    }

    return $element;
  }
}