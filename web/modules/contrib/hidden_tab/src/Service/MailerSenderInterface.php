<?php
/**
 * Created by PhpStorm.
 * User: milan
 * Date: 2/11/19
 * Time: 5:30 PM
 */

namespace Drupal\hidden_tab\Service;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\hidden_tab\Entity\HiddenTabPageInterface;

/**
 * Given a mailer, send corresponding mails.
 */
interface MailerSenderInterface {

  /**
   * Find mailer entity by params.
   *
   * @param \Drupal\hidden_tab\Entity\HiddenTabPageInterface|null $page
   *   The hidden tab page in question.
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   The entity in question.
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   The account in question.
   *
   * @return \Drupal\hidden_tab\Entity\HiddenTabMailerInterface[]
   *   Loaded entities
   */
  public function he(?HiddenTabPageInterface $page,
                     ?EntityInterface $entity,
                     ?AccountInterface $account): array;

  /**
   * @param \Drupal\hidden_tab\Entity\HiddenTabPageInterface $page
   *   The page in question.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The account in question.
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   The account in question.
   *
   * @return \Drupal\hidden_tab\Entity\HiddenTabMailerInterface[]
   *   Loaded entities
   */
  public function peu(HiddenTabPageInterface $page,
                      EntityInterface $entity,
                      AccountInterface $account): array;

  /**
   * @param \Drupal\hidden_tab\Entity\HiddenTabPageInterface $page
   *   The page in question.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity in question.
   * @param bool $account
   *   Dummy.
   *
   * @return \Drupal\hidden_tab\Entity\HiddenTabMailerInterface[]
   *   Loaded entities
   */
  public function pex(HiddenTabPageInterface $page,
                      EntityInterface $entity,
                      bool $account): array;

  /**
   * @param \Drupal\hidden_tab\Entity\HiddenTabPageInterface $page
   *   The page in question.
   * @param bool $entity
   *   Dummy.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account in question.
   *
   * @return \Drupal\hidden_tab\Entity\HiddenTabMailerInterface[]
   *   Loaded entities
   */
  public function pxu(HiddenTabPageInterface $page,
                      bool $entity,
                      AccountInterface $account): array;

  /**
   * @param \Drupal\hidden_tab\Entity\HiddenTabPageInterface $page
   *   The page in question.
   * @param bool $entity
   *   Dummy.
   * @param bool $account
   *   Dummy.
   *
   * @return \Drupal\hidden_tab\Entity\HiddenTabMailerInterface[]
   *   Loaded entities
   */
  public function pxx(?HiddenTabPageInterface $page,
                      bool $entity,
                      bool $account): array;

  /**
   * @param bool $page
   *   Dummy.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity in question.
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   The account in question.
   *
   * @return \Drupal\hidden_tab\Entity\HiddenTabMailerInterface[]
   *   Loaded entities
   */
  public function xeu(bool $page,
                      EntityInterface $entity,
                      AccountInterface $account): array;

  /**
   * @param bool $page
   *   Dummy.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity in question.
   * @param bool $account
   *   Dummy.
   *
   * @return \Drupal\hidden_tab\Entity\HiddenTabMailerInterface[]
   *   Loaded entities
   */
  public function xex(bool $page,
                      EntityInterface $entity,
                      bool $account): array;

  /**
   * @param bool $page
   *   Dummy.
   * @param bool $entity
   *   Dummy.
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   The account in question.
   *
   * @return \Drupal\hidden_tab\Entity\HiddenTabMailerInterface[]
   *   Loaded entities
   */
  public function xxu(bool $page,
                      bool $entity,
                      AccountInterface $account): array;

  /**
   * Execute the mailer.
   *
   * @param \Drupal\Core\Entity\EntityInterface|null $mailer
   *   The mailer to send.
   *
   * @return bool
   *   True on success.
   */
  public function send(?EntityInterface $mailer):bool ;

}