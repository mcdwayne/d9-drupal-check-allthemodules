<?php

namespace Drupal\hidden_tab\Service;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\hidden_tab\Entity\HiddenTabMailerInterface;
use Drupal\hidden_tab\Entity\HiddenTabPageInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class MailerSender implements MailerSenderInterface {

  /**
   * Used by findMailerEntityById().
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $mailerStorage;

  /**
   * To get current IP, for per ip accounting.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * MailerSender constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   See $this->>mailerStorage.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   See $this->request
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager,
                              RequestStack $request_stack) {
    $this->mailerStorage = $entity_type_manager->getStorage('hidden_tab_mailer');
    $this->request = $request_stack->getCurrentRequest();
  }

  /**
   * Find entity by id.
   *
   * @param $id
   *   Id of entity
   *
   * @return \Drupal\hidden_tab\Entity\HiddenTabMailerInterface
   *   Loaded entity if any.
   */
  private function findMailerEntityById($id): HiddenTabMailerInterface {
    /** @var \Drupal\hidden_tab\Entity\HiddenTabMailerInterface $mailer */
    $mailer = $this->mailerStorage->load($id);
    return $mailer;
  }

  // -------------------------------------------------------------- FIND CREDIT

  /**
   * {@inheritdoc}
   */
  public function he(?HiddenTabPageInterface $page,
                     ?EntityInterface $entity,
                     ?AccountInterface $account): array {
    if ($page === NULL && $entity === NULL && $account === NULL) {
      throw new \LogicException('illegal state');
    }
    $q = $this->mailerStorage
      ->getQuery()
      ->condition('status', TRUE, '=');

    if (!$page) {
      $q->condition('target_hidden_tab_page', NULL, 'IS NULL');
    }
    else {
      $q->condition('target_hidden_tab_page', $page->id(), '=');
    }

    if (!$entity) {
      $q->condition('target_entity', NULL, 'IS NULL');
    }
    else {
      $q->condition('target_entity', $entity->id(), '=');
    }

    if (!$account) {
      $q->condition('target_user', NULL, 'IS NULL');
    }
    else {
      $q->condition('target_user', $account->id(), '=');
    }

    $ret = [];
    foreach ($q->execute() as $id) {
      $ret[] = $this::findMailerEntityById($id);
    }
    return $ret;
  }


  /**
   * {@inheritdoc}
   */
  public function peu(HiddenTabPageInterface $page,
                      EntityInterface $entity,
                      AccountInterface $account): array {
    return $this::he($page, $entity, $account);
  }


  /**
   * {@inheritdoc}
   */
  public function pex(HiddenTabPageInterface $page,
                      EntityInterface $entity,
                      bool $account): array {
    if ($account) {
      throw new \LogicException('illegal state');
    }
    return $this::he($page, $entity, NULL);
  }


  /**
   * {@inheritdoc}
   */
  public function pxu(HiddenTabPageInterface $page,
                      bool $entity,
                      AccountInterface $account): array {
    if ($entity) {
      throw new \LogicException('illegal state');
    }
    return $this::he($page, NULL, $account);
  }

  /**
   * {@inheritdoc}
   */
  public function pxx(?HiddenTabPageInterface $page,
                      bool $entity,
                      bool $account): array {
    if ($entity || $account) {
      throw new \LogicException('illegal state');
    }
    return $this::he($page, NULL, NULL);
  }

  /**
   * {@inheritdoc}
   */
  public function xeu(bool $page,
                      EntityInterface $entity,
                      AccountInterface $account): array {
    if ($page) {
      throw new \LogicException('illegal state');
    }
    return $this::he(NULL, $entity, $account);
  }

  /**
   * {@inheritdoc}
   */
  public function xex(bool $page,
                      EntityInterface $entity,
                      bool $account): array {
    if ($page || $account) {
      throw new \LogicException('illegal state');
    }
    return $this::he(NULL, $entity, NULL);
  }

  /**
   * {@inheritdoc}
   */
  public function xxu(bool $page,
                      bool $entity,
                      AccountInterface $account): array {
    if ($page || $entity) {
      throw new \LogicException('illegal state');
    }
    return $this::he(NULL, NULL, $account);
  }

  /**
   * {@inheritdoc}
   */
  public function send(?EntityInterface $mailer): bool {
    return FALSE;
  }



  /**
   * Send an email (the secret link).
   *
   * @param string $mail
   *   The email address.
   * @param \Drupal\hidden_tab\Entity\HiddenTabPageInterface $page
   *   The page in question.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity in question.
   * @param \Drupal\hidden_tab\Entity\HiddenTabMailerInterface $mailer
   *   The mail configuration for the given page.
   *
   * @return bool
   *   True if success.
   */
  public static function email(string $mail,
                               HiddenTabPageInterface $page,
                               EntityInterface $entity,
                               HiddenTabMailerInterface $mailer): bool {
    $ok = \Drupal::service('plugin.manager.mail')->mail(
      'hidden_tab',
      'hidden_tab',
      $mail,
      // Lang will be handled by hidden_tab_mail() and $params.
      'en',
      [
        'page' => $page,
        'entity' => $entity,
        'mailer' => $mailer,
        // TODO find email's langcode (search in users) or fallback to site's
        // default.
        'langcode' => \Drupal::languageManager()->getDefaultLanguage()->getId(),
      ],
      NULL,
      TRUE
    );
    return $ok['result'] ? TRUE : FALSE;
  }


}
