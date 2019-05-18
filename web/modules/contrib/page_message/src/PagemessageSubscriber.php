<?php

/**
 * @file
 * Contains \Drupal\page_message\PagemessageSubscriber
 */

namespace Drupal\page_message;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Component\Utility\SafeMarkup;


class PagemessageSubscriber implements EventSubscriberInterface {

  public function processPageMessage(GetResponseEvent $event) {
    $current_path = \Drupal::service('path.current')->getPath();

    $config = \Drupal::config('page_message.settings', 0);
    $admin_only = $config->get('admin_only');
    if($admin_only && substr($current_path, 0, 6) != '/admin') {
        // Don't show messages for non-admin pages.
        return;
      }

    // Everyone who can get to the page sees the message, but only those
    //    with permission see the 'Edit' link.
    // Get the current user
    $user = \Drupal::currentUser();
    // Check for permission
    $show_edit_link = $user->hasPermission('administer page message');

    foreach ($entries = PagemessageStorage::search($current_path) as $entry) {

      if($show_edit_link) {
        // Build the Edit link.
        $link = '/admin/config/user-interface/page_message/add_update?pmid=' . $entry->pmid;
        $link_markup = '<a class="page-message-edit" href="' . $link . '">Edit</a>';
      }
      else {
        $link_markup = '';
      }

      $message = SafeMarkup::checkPlain($entry->message);

      $message_and_link = $message . $link_markup;

      // Prevent the link from being escaped by the render system.
      $checked_markup = \Drupal\Core\Render\Markup::create($message_and_link);

      drupal_set_message($checked_markup, 'page-message');
    }
  }

  /**
  * {@inheritdoc}
  */
  static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('processPageMessage');
    return $events;
  }
}


/*
 *     function page_message_page_attachment(&$page) {
  $page_url = current_path();

  // Check for admin-only.
   $admin_only = variable_get('page_message_admin_only', 0);
   if ($admin_only && substr($page_url, 5) != 'admin') {
    return;
   }

  // Lookup page URL in page_message table.
  $result = db_select('page_message', 'pm')
    ->fields('pm')
    ->condition('page', $page_url, '=')
    ->execute()
    ->fetchAll();

  // If we have any matches, set the message
  $message_number = 0;
  foreach ($result as $record) {
    $edit_link = l(t('Edit this Page Message message'),
                     'admin/config/user-interface/page_message/set_message',
                     array('query' => array(
                      'op' => 'update',
                      'pmid' => $record->pmid
                      ),
                           'attributes' => array(
                      'class' => array('page-message-edit')
                           )));
    $pr_message = <<<EOM
        $record->message
        $edit_link
EOM;

   drupal_set_message(check_markup($pr_message, 'filtered_html'), 'page-message');
   drupal_add_css(drupal_get_path('module', 'page_message') . '/page_message.css',
                  array('group' => CSS_DEFAULT, 'type' => 'file'));
  }
}
*/

