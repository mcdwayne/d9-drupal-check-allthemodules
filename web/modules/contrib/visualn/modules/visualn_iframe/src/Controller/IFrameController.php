<?php

namespace Drupal\visualn_iframe\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\block\Entity\Block;
use Drupal\visualn_iframe\Entity\VisualNIFrame;
use Drupal\visualn_drawing\Entity\VisualNDrawing;

/**
 * Provides content for the given iframe path by hash.
 *
 * @ingroup iframes_toolkit
 */
class IFrameController extends ControllerBase {

  // @todo: do not prvide any title for iframe ?
  //   maybe pass title as argument to content provider

  // @todo: create DefaultContentProvider class (see DefaultNegotiator in case of ThemeNegotiator)

  /**
   * Build iframe content.
   *
   * @return array
   *   Return content for the iframe.
   */
  public function build($hash) {
    $iframe_entity = VisualNIFrame::getIFrameEntityByHash($hash);
    if ($iframe_entity) {
      $deny_access = FALSE;

      // Do not show unpublished content even if user has permission to view it
      if (!$iframe_entity->isPublished() || !$iframe_entity->access('view')) {
        $deny_access = TRUE;
      }
      elseif ($drawing_id = $iframe_entity->getDrawingId()) {
        $visualn_drawing = VisualNDrawing::load($drawing_id);
        if ($visualn_drawing && !$visualn_drawing->access('view')) {
          $deny_access = TRUE;
        }
      }

      if ($deny_access) {
        // @todo: use template for default "not available" markup
        //   to allow developers override it
        $cache_tags = ['visualn_iframe:' . $iframe_entity->id()];
        $render = [
          '#markup' => t('Content not available'),
          '#cache' => [
            'tags' => $cache_tags,
          ],
        ];

        return $render;
      }

      // @todo: check token keys names
      $available_tokens = [
        '[visualn-iframe:location]' => (string) $iframe_entity->getLocation(),
      ];

      $handler_key = $iframe_entity->get('handler_key')->value;
      $data = $iframe_entity->getData();
      // @todo: check if settings is an array (?)
      $settings = $iframe_entity->getSettings();
      // collect services (via service tag), get responsibe iframe content provider
      $content_provider = \Drupal::service('visualn_iframe.content_provider');
      $drawing_markup = $content_provider->provideContent($handler_key, $data, $settings);
      // mark the entity as viewed
      // @todo: though it doesn't check if valid iframe markup provided
      if (!$iframe_entity->getViewed()) {
        $iframe_entity->setViewed(TRUE);
        $iframe_entity->save();
      }



      // Get and attach origin link (if enabled)
      $origin_url = '';
      $origin_title = '';
      $origin_link = '';


      $attach_settings_cache_tag = FALSE;

      $config = \Drupal::config('visualn_iframe.settings');


      // use defaults if setting found and enabled
      // or if use_defaults and show_link settings are not present
      $use_default_settings = !empty($settings['use_defaults'])
        || !isset($settings['use_defaults']) && !isset($settings['show_link']);

      $site_url = \Drupal\Core\Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString();
      $site_name = \Drupal::config('system.site')->get('name');

      // get origin url and title if 'show_link' enabled
      if ($use_default_settings) {
        if ($config->get('default.show_link')) {
          // @todo: config values should be trimmed in iframe config form (same for other values)
          $origin_url = $config->get('default.origin_url') ?: $site_url;
          $origin_title = $config->get('default.origin_title') ?: $site_name;
          $open_in_new_window = $config->get('default.open_in_new_window');

          // @todo: check fallback conditions (e.g. tokens not found)
        }

        // attach cache tag even if show_link disabled to reset it when the setting changes
        $attach_settings_cache_tag = TRUE;
      }
      elseif (!empty($settings['show_link'])) {
        $settings = array_filter($settings);
        $settings += [
          'origin_url' => $config->get('default.origin_url'),
          'origin_title' => $config->get('default.origin_title'),
          'open_in_new_window' => $config->get('default.open_in_new_window'),
        ];

        // @todo: check fallback conditions (e.g. tokens not found)

        // get origin_url and origin_title
        $origin_url = $settings['origin_url'] ?: $site_url;
        $origin_title = $settings['origin_title'] ?: $site_name;
        $open_in_new_window = $settings['open_in_new_window'];

        // @todo: only attach when needed
        $attach_settings_cache_tag = TRUE;
      }
      else {
        // show_link is disabled for the given iframe entry
      }

      // @todo: this is a dirty solution and it doesn't check fallback conditions
      $origin_url
        = str_replace('[visualn-iframe:location]', $available_tokens['[visualn-iframe:location]'], $origin_url);

      // get origin_link build
      if ($origin_url && $origin_title) {
        $origin_link = [
          '#type' => 'html_tag',
          '#tag' => 'a',
          '#value' => $origin_title,
          '#attributes' => [
            'href' => $origin_url,
          ],
        ];
        if ($open_in_new_window) {
          $origin_link['#attributes']['target'] = '_blank';
        }
      }

      // Add 'visualn_iframe_settings' cache tag only if settings defaults used.
      $cache_tags = $attach_settings_cache_tag ? ['visualn_iframe_settings'] : [];
      $cache_tags[] = 'visualn_iframe:' . $iframe_entity->id();
      $render = [
        '#content' => $drawing_markup,
        '#origin_link' => $origin_link,
        '#origin_title' => $origin_title,
        '#origin_url' => $origin_url,
        '#theme' => 'visualn_iframe_content',
        '#attached' => ['library' => [
          'visualn_iframe/visualn-iframe-content',
        ]],
        '#cache' => [
          'tags' => $cache_tags,
        ],
      ];


      /*
        '#cache' => [
          // @todo: add also hash-based cache tag, especially for 'record not found' case
          // @todo: check other cache tags assigned in cache_dynamic_page_cache,
          //   e.g. config:block_list is not needed there (or not?)
        ],
      */

    }
    else {
      // @todo: use template for default "not found" markup to allow developers override it
      // @todo: maybe just return page not found and enable parameters upcast
      //    (can be limited just to checking only hash length and valid chars to avoid
      //    obviously malicious requests)
      //    see https://www.drupal.org/docs/8/api/routing-system/parameter-upcasting-in-routes

      // Do not cache 'record not found' requests to avoid cache-overflow attacks
      // @todo: check if it is enough
      // @todo: log 'iframe entry not found' hash (iframes) requests
      $render = [
        '#markup' => t('No content found'),
        '#cache' => [
          'max-age' => 0,
        ],
      ];
    }

    return $render;
  }

}
