<?php

namespace Drupal\hidden_tab\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\hidden_tab\Entity\HiddenTabMailerInterface;
use Drupal\hidden_tab\Plugable\MailDiscovery\HiddenTabMailDiscoveryInterface;
use Drupal\hidden_tab\Plugable\MailDiscovery\HiddenTabMailDiscoveryPluginManager;
use Drupal\hidden_tab\Service\HiddenTabEntityHelperInterface;
use Drupal\hidden_tab\Utility;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route controller which sends the email.
 */
class SendMailController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Hidden Tab Page helper.
   *
   * @var \Drupal\hidden_tab\Service\HiddenTabEntityHelperInterface
   */
  protected $entityHelper;

  /**
   * Current request.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $reqStack;

  /**
   * To find mail plugins.
   *
   * @var \Drupal\hidden_tab\Plugable\MailDiscovery\HiddenTabMailDiscoveryPluginManager
   */
  protected $discMan;

  /**
   * To find nodes to send them mails.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $nodeStorage;

  /**
   * To find context maker plugins.
   *
   * @var \Drupal\hidden_tab\Plugable\TplContext\HiddenTabTplContextPluginManager
   */

  /**
   * Constructor.
   */
  public function __construct(RequestStack $req_stack,
                              EntityStorageInterface $node_storage,
                              HiddenTabEntityHelperInterface $entity_helper,
                              HiddenTabMailDiscoveryPluginManager $disc_man) {
    $this->reqStack = $req_stack;
    $this->nodeStorage = $node_storage;
    $this->entityHelper = $entity_helper;
    $this->discMan = $disc_man;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @noinspection PhpParamsInspection */
    return new static(
      $container->get('request_stack'),
      $container->get('entity_type.manager')->getStorage('node'),
      $container->get('hidden_tab.entity_helper'),
      $container->get('plugin.manager.hidden_tab_mail_discovery')
    );
  }

  /**
   * Displays the actual page, called from Tab page.
   *
   * @param \Drupal\node\NodeInterface $node
   *
   * @return array|Response
   *   Render array of komponents to put in the regions, as configured in the
   *   page's layout.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function send(HiddenTabMailerInterface $hidden_tab_mailer,
                       EntityInterface $target_entity): Response {
    $to = Utility::checkRedirect()->toString();
    if ($to) {
      $to = new RedirectResponse($to);
    }
    else {
      $to = new RedirectResponse('/');
    }

    $pid = HiddenTabMailDiscoveryInterface::PID;

    $discoveries = $hidden_tab_mailer->pluginConfigurations($pid);
    if (empty($discoveries) || !isset($discoveries['_active'][$pid])) {
      $this->messenger()->addWarning(t('Mail discovery not found'));
      return $to;
    }

    $d = [];
    foreach ($discoveries['_active'][$pid] as $activated_disc) {
      if (!$this->discMan->exists($activated_disc)) {
        $this->messenger()
          ->addWarning(t('Missing mail discovery plugin: @p', [
            '@p' => $activated_disc,
          ]));
        return $to;
      }
      $d[] = $this->discMan->plugin($activated_disc);
    }
    if (empty($d)) {
      $this->messenger()->addWarning(t('No email discovery plugin was found.'));
      return $to;
    }

    $mails = [];
    foreach ($d as $dd) {
      $mail = $dd->findMail(
        $hidden_tab_mailer, $hidden_tab_mailer->targetPageEntity(), $target_entity
      );
      if ($mail) {
        if (is_array($mail)) {
          $mails += $mail;
        }
        else {
          $mails[] = $mail;
        }
      }
    }

    if (empty($mails)) {
      $this->messenger()->addWarning(t('No email was found'));
      return $to;
    }

    foreach ($mails as $mail) {
      $ctx = [];
      Utility::email($mail, $hidden_tab_mailer->targetPageEntity(), $target_entity, $hidden_tab_mailer, $ctx);
    }

    $this->messenger()->addStatus(t('Emails sent'));
    return $to;
  }

  public function broadcast(HiddenTabMailerInterface $hidden_tab_mailer): Response {
    $pid = HiddenTabMailDiscoveryInterface::PID;

    $to = Utility::checkRedirect()->toString();
    if ($to) {
      $to = new RedirectResponse($to);
    }
    else {
      $to = new RedirectResponse('/');
    }

    if ($hidden_tab_mailer->targetEntityId()) {
      $this->messenger()
        ->addError($this->t('Can not broadcast while target entity is set.'));
      return $to;
    }
    if ($hidden_tab_mailer->targetUserId()) {
      $this->messenger()
        ->addError($this->t('Per user mailer is not implemented yet'));
      return $to;
    }
    if ($hidden_tab_mailer->targetEntityType() !== 'node') {
      $this->messenger()
        ->addError($this->t('Mailer only implemented for node entity type.'));
      return $to;
    }

    $discoveries = $hidden_tab_mailer->pluginConfigurations($pid);
    if (empty($discoveries) || !isset($discoveries['_active'][$pid])) {
      $this->messenger()->addWarning(t('Mail discovery not found'));
      return $to;
    }

    $d = [];
    foreach ($discoveries['_active'][$pid] as $activated_disc) {
      if (!$this->discMan->exists($activated_disc)) {
        $this->messenger()
          ->addWarning(t('Missing mail discovery plugin: @p', [
            '@p' => $activated_disc,
          ]));
        return $to;
      }
      $d[] = $this->discMan->plugin($activated_disc);
    }
    if (empty($d)) {
      $this->messenger()->addWarning(t('No email discovery plugin was found.'));
      return $to;
    }

    // TODO implement batch.
    $ids = [];
    $nodes = $hidden_tab_mailer->targetEntityBundle()
      ? $this->nodeStorage->loadByProperties([
        'type' => $hidden_tab_mailer->targetEntityBundle(),
      ])
      : $this->nodeStorage->loadMultiple();
    foreach ($nodes as $target_entity) {
      $mails = [];
      foreach ($d as $dd) {
        $mail = $dd->findMail(
          $hidden_tab_mailer, $hidden_tab_mailer->targetPageEntity(), $target_entity
        );
        if ($mail) {
          if (is_array($mail)) {
            $mails += $mail;
          }
          else {
            $mails[] = $mail;
          }
        }
      }
      foreach ($mails as $mail) {
        $ctx = [];
        $ids[] = $target_entity->id();
        Utility::email($mail, $hidden_tab_mailer->targetPageEntity(), $target_entity, $hidden_tab_mailer, $ctx);
      }
    }
    $this->messenger()->addStatus(t('Email sent: @to', [
      '@to' => implode(', ', $ids),
    ]));

    return $to;
  }

}
