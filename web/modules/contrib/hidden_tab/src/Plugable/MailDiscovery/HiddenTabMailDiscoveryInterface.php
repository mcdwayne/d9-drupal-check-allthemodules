<?php

namespace Drupal\hidden_tab\Plugable\MailDiscovery;

use Drupal\Core\Entity\EntityInterface;
use Drupal\hidden_tab\Entity\HiddenTabMailerInterface;
use Drupal\hidden_tab\Entity\HiddenTabPageInterface;
use Drupal\hidden_tab\Plugable\HiddenTabPluginInterfaceBase;

/**
 * Plugin helping discover email addresses.
 *
 * @see \Drupal\hidden_tab\Entity\HiddenTabMailerInterface
 */
interface HiddenTabMailDiscoveryInterface extends HiddenTabPluginInterfaceBase {

  const PID = 'hidden_tab_mail_discovery';

  /**
   * Find the emails, according to plugins implementation.
   *
   * @param \Drupal\hidden_tab\Entity\HiddenTabMailerInterface $mailer
   *   Mailing config.
   * @param \Drupal\hidden_tab\Entity\HiddenTabPageInterface $page
   *   The page email is being sent for.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The node email is being sent for.
   *
   * @return string[]
   *   The emails, if any.
   */
  public function findMail(HiddenTabMailerInterface $mailer,
                           HiddenTabPageInterface $page,
                           EntityInterface $entity): array;

}
