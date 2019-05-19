<?php

namespace Drupal\views_show_more\EventSubscriber;

use Drupal\views\Ajax\ViewAjaxResponse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Response subscriber to handle AJAX responses.
 */
class ShowMoreEventSubscriber implements EventSubscriberInterface {

  /**
   * Renders the ajax commands right before preparing the result.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The response event, which contains the possible AjaxResponse object.
   */
  public function onResponse(FilterResponseEvent $event) {
    $response = $event->getResponse();

    // Only alter views ajax responses.
    if (!($response instanceof ViewAjaxResponse)) {
      return;
    }

    $view = $response->getView();
    // Only alter commands if the user has selected our pager and it attempting
    // to move beyond page 0.
    if ($view->getPager()->getPluginId() !== 'show_more' || $view->getCurrentPage() === 0) {
      return;
    }

    $commands = &$response->getCommands();
    foreach ($commands as $key => $command) {
      // Remove "viewsScrollTop" command, not needed.
      if ($command['command'] == 'viewsScrollTop') {
        unset($commands[$key]);
      }

      // The replace should the only one, but just in case, we'll make sure.
      if ($command['command'] == 'insert') {
        $style_plugin = $view->style_plugin->getPluginDefinition()['id'];
        $options = &$view->style_plugin->options;
        $pager_options = $view->pager->options;

        if ($style_plugin == 'html_list' && in_array($options['type'], ['ul', 'ol'])) {
          $target = "> {$options['type']}";
          if (!empty($options['wrapper_class'])) {
            $wrapper_classes = str_replace(' ', '.', $options['wrapper_class']);
            $target = ".{$wrapper_classes} {$target}";
          }
          $commands[$key]['append_at'] = $target;
        }
        elseif ($style_plugin == 'table') {
          $commands[$key]['append_at'] = '.views-table tbody';
        }
        elseif ($style_plugin == 'grid') {
          $commands[$key]['append_at'] = '.views-view-grid';
        }

        $commands[$key]['command'] = 'viewsShowMore';
        $commands[$key]['method'] = $pager_options['result_display_method'];
        if (isset($pager_options['effects']) && $pager_options['effects']['type'] != 'none') {
          $commands[$key]['effect'] = $pager_options['effects']['type'];
          $commands[$key]['speed'] = $pager_options['effects']['speed'];
        }
        $commands[$key]['options'] = [
          'content_selector' => $pager_options['advance']['content_selector'],
          'pager_selector' => $pager_options['advance']['pager_selector'],
        ];
      }

    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [KernelEvents::RESPONSE => [['onResponse']]];
  }

}
