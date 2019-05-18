<?php
namespace Drupal\EZProxy\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Routing\TrustedRedirectResponse;

class EZProxyController {

/**
 * Used with the CGI authentication method
 * see http://www.oclc.org/support/documentation/ezproxy/usr/cgi2.htm
 *
 * To use this method copy this line into the users.txt file (part of the ezproxy package)
 * ::CGI=http://example.com/ezproxylogin?url=^U 
 */
function ezproxy_login() {
  $user = \Drupal::currentUser();

  $ezproxy_url = isset($_REQUEST['url']) ? $_REQUEST['url'] : '';

  if ($user->id() && $user->hasPermission('access ezproxy content')) { //user is already logged in has permission to access ezproxy content
     $url = ezproxy_create_ticket($ezproxy_url);
     return new TrustedRedirectResponse($url);
  }
  elseif ($user->id() && !$user->hasPermission('access ezproxy content')) { //user is already logged in but does NOT have permission to access ezproxy content
    drupal_set_message(t('You do not have permission to access EZProxy content'), 'error');
    return '';
  }
  else {
    return new RedirectResponse(\Drupal::url('user.login'));
  }
}

/**
 * Redirects to the standard EZproxy logout screen
 */
function ezproxy_logout() {
  $ezproxy_host = \Drupal::config('ezproxy.settings')->get('ezproxy_host');
  $ezproxy_port = \Drupal::config('ezproxy.settings')->get('ezproxy_port');
  $url = $ezproxy_host . ':' . $ezproxy_port . '/logout';
  return new TrustedRedirectResponse($url);
}

/**
 * Redirects to the standard EZproxy screen
 */
function ezproxy_database_list() {
  $ezproxy_host = \Drupal::config('ezproxy.settings')->get('ezproxy_host');
  $ezproxy_port = \Drupal::config('ezproxy.settings')->get('ezproxy_port');
  $url = $ezproxy_host . ':' . $ezproxy_port . '/menu';
  return new TrustedRedirectResponse($url);
}

}
?>