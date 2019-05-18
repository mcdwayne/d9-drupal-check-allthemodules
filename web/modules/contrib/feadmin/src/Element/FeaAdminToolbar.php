<?php

/**
 * @file
 * Contains \Drupal\feadmin\Element\FeaAdminToolbar.
 * 
 * Sponsored by: www.freelance-drupal.com
 */

namespace Drupal\feadmin\Element;

use Drupal\Component\Utility\Html;
use Drupal\Core\Link;
use Drupal\Core\Render\Element\RenderElement;
use Drupal\feadmin\FeAdminTool\FeAdminToolBase;

/**
 * Provides a render element for the feadmin Toolbar.
 *
 * This toolbar allows you administer the various elements of your website,
 * directly from front-end.
 *
 * @RenderElement("feadmin_toolbar")
 */
class FeaAdminToolbar extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#pre_render' => [
        [get_class($this), 'preRenderFeaAdminToolbar'],
      ],
      '#attached' => [
        'library' => [
          'feadmin/feadmin.toolbar',
        ],
      ],
    ];
  }

  /**
   * Builds the Toolbar as a structured array ready for drupal_render().
   *
   * Since building the toolbar takes some time, it is done just prior to
   * rendering to ensure that it is built only if it will be displayed.
   *
   * @param array $element
   *   A structured array.
   *
   * @return array
   *   A renderable array.
   */
  public static function preRenderFeaAdminToolbar(array $element) {
    // Toolbar element.
    $element['feadmin_toolbar'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'id' => Html::getUniqueId('feadmin-toolbar'),
        'class' => ['toolbar'],
      ),
    );
    $element['feadmin_toolbar']['content'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array(
          'toolbar-tray-vertical',
          'toolbar-tray-vertical-right',
          'toolbar-tray-content'
        ),
      ),
    );

    // If module Toolbar is not available, found another way to collapse the
    // toolbar.
    if (!\Drupal::moduleHandler()->moduleExists('toolbar')) {
      $element['feadmin_toolbar']['#attributes']['class'][] = 'collapsed';
      $element['feadmin_toolbar']['content']['toogler'] = array (
        '#prefix' => '<div class="toolbar-tray-toogler">',
        '#markup' => 'Toogle me',
        '#suffix' => '</div>',
      );
    }

    self::addToolListElement($element['feadmin_toolbar']['content']);
    return $element;
  }

  /**
   * Add structured array for each tools to display in the toolbar.
   *
   * @param array $element
   *   A structured array.
   */
  public static function addToolListElement(&$element) {

    // Retrieve all available tool definitions.
    $tool_definitions = \Drupal::service('plugin.manager.feadmin.tool')
      ->getDefinitions();

    // Retrieve all tools such as configured.
    $configured_tools = \Drupal::config('feadmin.settings')->get('tools');

    if (empty($configured_tools)) {
      $element['empty'] = array(
        '#prefix' => '<div class="empty-toolbar">',
        '#markup' => t('No tools enabled yet. Visit @link to activate available tools.', array('@link' => Link::createFromRoute(t('administration page'), 'feadmin.settings')->toString())),
        '#suffix' => '</div>',
      );
    }
    else {
      foreach ($tool_definitions as $id => $tool_definition) {

        // Only add if the tool is activated.
        if (isset($configured_tools[$id]) && $configured_tools[$id]['enabled']) {
          /** @var \Drupal\feadmin\FeAdminTool\FeAdminToolBase $tool_instance */
          $tool_instance = \Drupal::service('plugin.manager.feadmin.tool')
            ->createInstance($id);
          $is_accessible = $tool_instance->access(\Drupal::currentUser());
          $accessible = $is_accessible ? '' : 'ui-state-disabled';
          $element[$id] = array(
            '#type' => 'container',
            '#prefix' => '<h3 data-toolbar-tool="' . $id . '" class="' . $accessible . ' ' . $id . '">' . $tool_definition['label'] . '</h3>',
            'description' => array(
              '#markup' => '<div class="description">' . $tool_definition['description'] . '</div>',
            ),
            '#attributes' => array(
              'id' => array('feadmin-tool-' . $id),
              'class' => array('feadmin-tool', $id),
            ),
          );
          if ($is_accessible) {
            $config = $tool_instance->build();
            if (!empty($config)) {
              $element[$id] += $config;
            }
          }
        }
      }
    }
  }

}
