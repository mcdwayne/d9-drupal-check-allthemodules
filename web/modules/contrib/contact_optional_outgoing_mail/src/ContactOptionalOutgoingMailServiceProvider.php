<?php

namespace Drupal\contact_optional_outgoing_mail;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Class ContactOptionalOutgoingMailServiceProvider.
 *
 * This replaces the contact.mail_handler services with our custom
 * implementation to make sending mails optional.
 *
 * @see \Drupal\contact_optional_outgoing_mail\ContactMailHandler
 *
 * @package Drupal\contact_optional_outgoing_mail
 */
class ContactOptionalOutgoingMailServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $definition = $container->getDefinition('contact.mail_handler');
    if ($definition->getClass() !== 'Drupal\contact\MailHandler') {
      throw new \RuntimeException('contact.mail_handler has a different class than expected: ' . $definition->getClass() . '. contact_optional_outgoing_mail might not work properly.');
    }

    $definition->setClass('Drupal\contact_optional_outgoing_mail\ContactMailHandler');
  }

}
