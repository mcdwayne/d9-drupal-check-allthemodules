<?php
namespace Drupal\site_under_construction\EventSubscriber;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
class SiteUnderConstructionSubscriber implements EventSubscriberInterface {
  public function implementTemplate(GetResponseEvent $event) {
  global $base_url;
  // Check if user using drush then return before going to template add process.
  if(php_sapi_name() == 'cli') return ;

  $account = \Drupal::currentUser();
  $enable = \Drupal::state()->get('site_under_construction_enable', FALSE);

  $template = \Drupal::state()->get('site_under_construction_templates', '');
  // Resolve drush crash issue $base_url == 'http://default'
  if ($base_url == 'http://default' || !$enable || empty($template) || ($account->id() > 0) || arg(0) == 'user') {
    return;
  }
  $favicon = \Drupal::state()->get('site_under_construction_favicon', 'core/misc/favicon.ico');
  $title = \Drupal::state()->get('site_under_construction_title', 'Home');
  echo '<link type="image/vnd.microsoft.icon" href="' . $base_url . '/' . $favicon . '" rel="shortcut icon">';
  echo '<title>' . $title . '</title>';
  echo '<link rel="stylesheet" type="text/css" href="' . drupal_get_path('module', 'site_under_construction') . '/css/style.css' . '"/>';
  echo '<iframe  src="' . $base_url . '/' . $template . '" name="iframe_a" seamless></iframe> ';
  exit();
  }
  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('implementTemplate');
    return $events;
  }
}
?>
