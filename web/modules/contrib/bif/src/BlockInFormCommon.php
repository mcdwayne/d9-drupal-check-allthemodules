<?php
/**
 * @file
 */

namespace Drupal\block_in_form;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;

trait BlockInFormCommon {
  /**
   * @param $block
   * @param null $display
   * @return \Drupal\Core\Entity\Display\EntityDisplayInterface|null
   */
  public function blockInFormSave($block, $display = NULL) {
    if ($display === NULL) {
      if ($block->context == 'form') {
        $display = EntityFormDisplay::load($block->entity_type . '.' . $block->bundle . '.' . $block->mode);
      }
      elseif ($block->context == 'view') {
        $display = EntityViewDisplay::load($block->entity_type . '.' . $block->bundle . '.' . $block->mode);
      }
    }

    // If no display was found. It doesn't exist yet, create it.
    if (!isset($display)) {
      if ($block->context == 'form') {
        $display = EntityFormDisplay::create(array(
          'targetEntityType' => $block->entity_type,
          'bundle' => $block->bundle,
          'mode' => $block->mode,
        ))->setStatus(TRUE);
      }
      elseif ($block->context == 'view') {
        $display = EntityViewDisplay::create(array(
          'targetEntityType' => $block->entity_type,
          'bundle' => $block->bundle,
          'mode' => $block->mode,
        ))->setStatus(TRUE);
      }
    }

    /**
     * @var $display \Drupal\Core\Entity\Display\EntityDisplayInterface
     */
    if (isset($display)) {
      $data = (array) $block;
      unset($data['block_name'], $data['entity_type'], $data['bundle'], $data['mode'], $data['form'], $data['context']);
      $display->setThirdPartySetting('block_in_form', $block->block_name, $data);
      $display->save();
    }

    return $display;
  }

  /**
   * Get all groups.
   *
   * @param $entity_type
   *   The name of the entity.
   * @param $bundle
   *   The name of the bundle.
   * @param $context
   *   The context of the view mode (form or view)
   * @param $mode
   *   The view mode.
   */
  public function infoBlocks($entity_type, $bundle, $context, $mode) {
    if ($context == 'form') {
      $display = EntityFormDisplay::load($entity_type . '.' . $bundle . '.' . $mode);
      if (!$display) {
        return array();
      }
      $data = $display->getThirdPartySettings('block_in_form');
    }
    if ($context == 'view') {
      $display = EntityViewDisplay::load($entity_type . '.' . $bundle . '.' . $mode);
      if (!$display) {
        return array();
      }
      $data = $display->getThirdPartySettings('block_in_form');
    }
    $blocks = [];
    if (isset($data)) {
      foreach ($data as $block_name => $definition) {
        $definition += array(
          'block_name' => $block_name,
          'entity_type' => $entity_type,
          'bundle' => $bundle,
          'context' => $context,
          'mode' => $mode,
        );
        $blocks[$block_name] = (object) $definition;
      }
    }

    return $blocks;
  }

  /**
   * @param $block_name
   * @param $entity_type
   * @param $bundle
   * @param $context
   * @param $mode
   * @return mixed
   */
  public function loadBlock($block_name, $entity_type, $bundle, $context, $mode) {
    $blocks = $this->infoBlocks($entity_type, $bundle, $context, $mode);
    if (isset($blocks[$block_name])) {
      return $blocks[$block_name];
    }
  }

  /**
   * Delete a block in form.
   *
   * @param $block
   *   A group definition.
   */
  public function deleteBlock($block) {
    if ($block->context == 'form') {
      $display = EntityFormDisplay::load($block->entity_type . '.' . $block->bundle . '.' . $block->mode);
    }
    elseif ($block->context == 'view') {
      $display = EntityViewDisplay::load($block->entity_type . '.' . $block->bundle . '.' . $block->mode);
    }

    /**
     * @var $display \Drupal\Core\Entity\Display\EntityDisplayInterface
     */
    if (isset($display)) {
      $display->unsetThirdPartySetting('block_in_form', $block->group_name);
      $display->save();
    }

    \Drupal::moduleHandler()->invokeAll('block_in_group_delete_block', array($block));
  }

  /**
   * Preprocess/ Pre-render callback.
   *
   * @see field_group_form_pre_render()
   * @see field_group_theme_registry_alter
   * @see field_group_fields_nest()
   * @param $vars preprocess vars or form element
   * @param $context The display context (form or view)
   * @return $element Array with re-arranged fields in groups.
   */
  public function buildEntityBlocks(&$vars, $context = 'view') {
    if ($context == 'form') {
      $element = &$vars;
    }
    else {
      if (isset($vars['elements'])) {
        $element = &$vars['elements'];
      }
      elseif (isset($vars['content'])) {
        $element = &$vars['content'];
      }
      else {
        if ($context === 'eck_entity') {
          $element = &$vars['entity'];
        }
        else {
          $element = &$vars;
        }
      }
    }

    // Create all groups and keep a flat list of references to these groups.
    $block_references = array();
    foreach ($element['#blocksinform'] as $block_name => $block) {
      // Construct own weight, as some fields (for example preprocess fields) don't have weight set.
      $element[$block_name] = array();
      $block_references[$block_name] = &$element[$block_name];
    }

    // Bring extra element wrappers to achieve a grouping of fields.
    // This will mainly be prefix and suffix altering.
    foreach ($element['#blocksinform'] as $block_name => $block) {
      $this->blockPreRender($block_references[$block_name], $block, $element);
    }

    // Allow others to alter the pre_rendered build.
    \Drupal::moduleHandler()->alter('block_in_form_build_pre_render', $element);

    // Return the element on forms.
    if ('form' == $context) {
      return $element;
    }
    elseif ('view' == $context) {
      $element['#theme'] = 'block';
    }

    // No groups on the entity. Prerender removed empty field groups.
    if (empty($element['#blocksinform'])) {
      return $element;
    }

    // Put groups inside content if we are rendering an entity_view.
    foreach ($element['#blocksinform'] as $block) {
      if (!empty($element[$block->block_name])) {
        if (isset($vars['content'])) {
          $vars['content'][$block->block_name] = $element[$block->block_name];
        }
        elseif (isset($vars['user_profile'])) {
          $vars['user_profile'][$block->block_name] = $element[$block->block_name];
        }
      }
    }

    return $vars;
  }
}