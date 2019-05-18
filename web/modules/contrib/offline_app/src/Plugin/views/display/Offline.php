<?php

/**
 * @file
 * Contains \Drupal\offline_app\Plugin\views\display\Offline.
 */

namespace Drupal\offline_app\Plugin\views\display;

use Drupal\views\Plugin\views\display\DisplayPluginBase;

/**
 * The plugin that handles an offline display.
 *
 * @ingroup views_display_plugins
 *
 * @todo: Wait until annotations/plugins support access methods.
 * no_ui => !\Drupal::config('views.settings')->get('ui.show.display_embed'),
 *
 * @ViewsDisplay(
 *   id = "offline",
 *   title = @Translation("Offline"),
 *   help = @Translation("Provide a display which can be used for offline display"),
 *   theme = "views_view",
 *   uses_menu_links = FALSE
 * )
 */
class Offline extends DisplayPluginBase {}
