<?php

namespace Drupal\saml_sp\EventSubscriber;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use OneLogin\Saml2\Utils;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribes to relevant events.
 */
class SamlSpSubscriber implements EventSubscriberInterface {

  /**
   * Checks to be sure the certificate has not expired.
   */
  public function checkForCertExpiration(GetResponseEvent $event) {
    $config = \Drupal::config('saml_sp.settings');
    $user = \Drupal::currentUser();
    if ($user->hasPermission('configure saml sp') &&
      function_exists('openssl_x509_parse') &&
      !empty($config->get('cert_location')) &&
      file_exists($config->get('cert_location'))
    ) {
      $encoded_cert = trim(file_get_contents($config->get('cert_location')));
      $cert = openssl_x509_parse(Utils::formatCert($encoded_cert));
      $test_time = REQUEST_TIME;
      if ($cert['validTo_time_t'] < $test_time) {
        $markup = new TranslatableMarkup('Your site\'s SAML certificate is expired. Please replace it with another certificate and request an update to your Relying Party Trust (RPT). You can enter in a location for the new certificate/key pair on the <a href="@url">SAML Service Providers</a> page. Until the certificate/key pair is replaced your SAML authentication service will not function.', [
          '@url' => \Drupal::url('saml_sp.admin'),
        ]);
        \Drupal::messenger()->addMessage($markup, MessengerInterface::TYPE_ERROR, FALSE);
      }
      elseif (($cert['validTo_time_t'] - $test_time) < (60 * 60 * 24 * 30)) {
        $markup = new TranslatableMarkup('Your site\'s SAML certificate will expire in %interval. Please replace it with another certificate and request an update to your Relying Party Trust (RPT). You can enter in a location for the new certificate/key pair on the <a href="@url">SAML Service Providers</a> page. Failure to update this certificate and update the Relying Party Trust (RPT) will result in the SAML authentication service not working.', [
          '%interval' => \Drupal::service('date.formatter')->formatInterval($cert['validTo_time_t'] - $test_time, 2),
          '@url' => \Drupal::url('saml_sp.admin'),
        ]);
        \Drupal::messenger()->addMessage($markup, MessengerInterface::TYPE_WARNING, FALSE);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['checkForCertExpiration'];
    return $events;
  }

}
