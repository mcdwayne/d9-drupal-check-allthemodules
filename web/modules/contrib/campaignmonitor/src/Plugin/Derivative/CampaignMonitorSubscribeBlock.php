<?php

namespace Drupal\campaignmonitor\Plugin\Derivative;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides block plugin definitions for all Views block displays.
 *
 * @see \Drupal\views\Plugin\block\block\ViewsBlock
 */
class CampaignMonitorSubscribeForm implements ContainerDeriverInterface {

  /**
   * List of derivative definitions.
   *
   * @var array
   */
  protected $derivatives = [];

  /**
   * The base plugin ID.
   *
   * @var string
   */
  protected $basePluginId;

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    // Get all campaign monitor lists for the current account
    foreach ($this->viewStorage->loadMultiple() as $view) {
      // Do not return results for disabled views.
      if (!$view->status()) {
        continue;
      }
      $executable = $view->getExecutable();
      $executable->initDisplay();
      foreach ($executable->displayHandlers as $display) {
        // Add a block plugin definition for each block display.
        if (isset($display) && !empty($display->definition['uses_hook_block'])) {
          $delta = $view->id() . '-' . $display->display['id'];

          $admin_label = $display->getOption('block_description');
          if (empty($admin_label)) {
            if ($display->display['display_title'] == $display->definition['title']) {
              $admin_label = $view->label();
            }
            else {
              // Allow translators to control the punctuation. Plugin
              // definitions get cached, so use TranslatableMarkup() instead of
              // t() to avoid double escaping when $admin_label is rendered
              // during requests that use the cached definition.
              $admin_label = new TranslatableMarkup('@view: @display', [
                '@view' => $view->label(),
                '@display' => $display->display['display_title'],
              ]);
            }
          }

          $this->derivatives[$delta] = [
            'category' => $display->getOption('block_category'),
            'admin_label' => $admin_label,
            'config_dependencies' => [
              'config' => [
                $view->getConfigDependencyName(),
              ],
            ],
          ];
          $this->derivatives[$delta] += $base_plugin_definition;
        }
      }
    }
    return $this->derivatives;
  }

}
