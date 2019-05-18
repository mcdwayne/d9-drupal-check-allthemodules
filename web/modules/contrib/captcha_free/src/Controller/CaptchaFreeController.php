<?php
/**
 * @file
 * Generate a cookie for anonymous user with the
 * needed http headers. This is done outside the normal Drupal
 * flow to avoid issues with the option to cache pages for Anonymous
 * users.
 */

// modules/captcha-free/src/Controller/CaptchaFreeController.php
namespace Drupal\captcha_free\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class CaptchaFreeController
{
    public function indexAction()
    {
$captcha_free_config = \Drupal::config('captcha_free.settings');
$secret_salt = $captcha_free_config->get('captcha_free_secret_salt');
$ct = time();
// We create a new cookie
$cookie = new Cookie('capfree', md5($secret_salt . $ct), 0, '/');

// we implement a new response object (be careful! One response object per query!)
$response = new Response();

// and we pass it the cookie we have created
$response->headers->setCookie($cookie);

// we implement the content to display ( if we need )
return $response->setContent($ct);
    }
}