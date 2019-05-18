<?php

namespace Drupal\custom_panels_blocks\Controller;

use Drupal\panels\Controller\Panels;
use Symfony\Component\HttpFoundation\Request;

/**
 * Overrides panels select block controller.
 */
class CustomPanelsBlocksController extends Panels {

  /**
   * Presents a list of blocks to add to the variant.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param string $machine_name
   *   The identifier of the block display variant.
   * @param string $tempstore_id
   *   The identifier of the temporary store.
   *
   * @return array
   *   The block selection page.
   */
  public function selectBlock(Request $request, $machine_name, $tempstore_id) {
    $cached_values = $this->getCachedValues($this->tempstore, $tempstore_id, $machine_name);
    /** @var \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant $variant_plugin */
    $variant_plugin = $cached_values['plugin'];
    /** @var \Drupal\panels\Plugin\PanelsPattern\PanelsPatternInterface $pattern_plugin */
    $pattern_plugin = $variant_plugin->getPattern();

    $contexts = $pattern_plugin->getDefaultContexts($this->tempstore, $tempstore_id, $machine_name);
    $variant_plugin->setContexts($contexts);

    // Add a section containing the available blocks to be added to the variant.
    $build = [
      '#type' => 'container',
      '#attached' => [
        'library' => [
          'core/drupal.ajax',
        ],
      ],
    ];
    $available_plugins = $this->blockManager->getDefinitionsForContexts($variant_plugin->getContexts());
    // Order by category, and then by admin label.
    $available_plugins = $this->blockManager->getSortedDefinitions($available_plugins);

    // Filter blocks by role permission.
    if (!empty($available_plugins)) {
      $config = _custom_panels_blocks_get_config();
      $current_user = \Drupal::currentUser();
      $roles = $current_user->getRoles();
      if (!empty($roles)) {
        // Get permissions of role.
        $role_panels_filter = [];
        foreach ($roles as $role) {
          $role_panels_filter += $config->get($role) ? $config->get($role) : [];
        }
        foreach ($available_plugins as $plugin_id => $plugin_definition) {
          // Make a section for each region.
          $category = _custom_panels_blocks_category_blocks($plugin_definition['category']);
          $category_key = 'category:' . $category;
          if (!isset($build[$category_key])) {
            $build[$category_key] = [
              '#type' => 'fieldgroup',
              '#title' => $category,
              'content' => [
                '#theme' => 'links',
              ],
            ];
          }
          // Show block if has permissions.
          if (array_key_exists($plugin_id, $role_panels_filter)) {
            $build[$category_key]['content']['#links'][$plugin_id] = [
              'title' => $plugin_definition['admin_label'],
              'url' => $pattern_plugin->getBlockAddUrl($tempstore_id, $machine_name, $plugin_id, $request->query->get('region'), $request->query->get('destination')),
              'attributes' => $this->getAjaxAttributes(),
            ];
          }
          // Remove category if is empty.
          if (empty($build[$category_key]['content']['#links'])) {
            unset($build[$category_key]);
          }
        }
        // Message if all categories are empty.
        if (count($build) == 2) {
          $build['#markup'] = $this->t('You don´t have any permissions to manage panels blocks.');
        }
      }
    }
    return $build;
  }

}
