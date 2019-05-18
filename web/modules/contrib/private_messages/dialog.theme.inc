<?php

/**
 * @file
 * Contains dialog.page.inc.
 *
 * Page callback for Dialog entities.
 */

use Drupal\Core\Link;
use Drupal\Core\Render\Element;

/**
 * Prepares variables for Dialog templates.
 *
 * Default template: dialog.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_dialog(array &$variables) {
  /** @var \Drupal\private_messages\Entity\DialogInterface $dialog */
  $dialog = $variables['elements']['#dialog'];
  $variables['dialog'] = $dialog;

  $variables['view_mode'] = $view_mode = $variables['elements']['#view_mode'];
  $variables['page'] = ($variables['view_mode'] == 'full' && dialog_is_page($dialog));
  $variables['url'] = $dialog->toUrl('canonical');

  $variables['messages_count'] = $dialog->get('messages_count')->value;
  $variables['new_count'] = $dialog->getNewMessagesCount();

  $recipients_name = $dialog->getParticipant()->getDisplayName();

  $variables['recipient'] = Link::createFromRoute($recipients_name,
    'entity.user.canonical',
    ['user' => $dialog->getParticipantId()])->toString();

  switch ($view_mode) {
    case 'full':
      $view = views_embed_view('messages', 'block_dialog', $dialog->id());
      $message = \Drupal::entityTypeManager()->getStorage('message')->create();

      $variables['messages'] = \Drupal::service('renderer')->render($view);
      $variables['form'] = \Drupal::service('entity.form_builder')
        ->getForm($message, 'default');
      break;
  }

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}

/**
 * Implements hook_theme_suggestions_HOOK().
 */
function private_messages_theme_suggestions_dialog_alter(
  array &$suggestions,
  array $variables
) {
  $sanitized_view_mode = strtr($variables['elements']['#view_mode'], '.', '_');

  $suggestions[] = 'dialog__' . $sanitized_view_mode;
  return $suggestions;
}
