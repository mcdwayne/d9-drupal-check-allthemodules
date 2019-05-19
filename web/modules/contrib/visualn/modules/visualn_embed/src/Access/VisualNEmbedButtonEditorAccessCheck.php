<?php

namespace Drupal\visualn_embed\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\editor\EditorInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

class VisualNEmbedButtonEditorAccessCheck implements AccessInterface {

  const VISUALN_EMBED_BUTTON_ID = 'Visualn-drawing-ckeditor-button';

  // @note: based on EmbedButtonEditorAccessCheck from embed module

  /**
   * Checks whether the visualn_embed button is enabled for the given text editor.
   *
   * Returns allowed if the editor toolbar contains the visualn_embed button or neutral
   * otherwise.
   *
   * @code
   * pattern: '/foo/{editor}'
   * requirements:
   *   _visualn_embed_button_editor_access: 'TRUE'
   * @endcode
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(RouteMatchInterface $route_match, AccountInterface $account) {
    $parameters = $route_match->getParameters();

    $access_result = AccessResult::allowedIf($parameters->has('editor'))
      // Vary by 'route' because the access result depends on the 'editor' route parameter.
      ->addCacheContexts(['route']);

    if ($access_result->isAllowed()) {
      $editor = $parameters->get('editor');
      if ($editor instanceof EditorInterface) {
        return $access_result
          // Besides having the 'editor' route parameter, it's also necessary to
          // be allowed to use the text format associated with the text editor.
          ->andIf($editor->getFilterFormat()->access('use', $account, TRUE))
          // And on top of that, the visualn_embed button needs to be present in the
          // text editor's configured toolbar.
          ->andIf($this->checkButtonEditorAccess($editor));
      }
      else {
        return AccessResult::forbidden();
      }
    }

    return $access_result;
  }

  /**
   * Checks if the visualn_embed button is enabled in an editor configuration.
   *
   * @param \Drupal\editor\EditorInterface $editor
   *   The editor entity to check.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   When the received Text Editor entity does not use CKEditor. This is
   *   currently only capable of detecting buttons used by CKEditor.
   */
  protected function checkButtonEditorAccess(EditorInterface $editor) {
    if ($editor->getEditor() !== 'ckeditor') {
      throw new HttpException(500, 'Currently, only CKEditor is supported.');
    }

    $has_button = FALSE;
    $settings = $editor->getSettings();
    foreach ($settings['toolbar']['rows'] as $row) {
      foreach ($row as $group) {
        if (in_array(self::VISUALN_EMBED_BUTTON_ID, $group['items'])) {
          $has_button = TRUE;
          break 2;
        }
      }
    }

    return AccessResult::allowedIf($has_button)
      ->addCacheableDependency($editor);
  }

}
