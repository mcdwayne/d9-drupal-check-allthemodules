<?php

namespace Drupal\follow_unfollow;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Mail\MailManager;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the send mail functionality.
 *
 * @see Drupal\follow_unfollow\Form\FollowUnfollowForm
 */
class FollowUnfollowSendMail {
  /**
   * The config_factory variable.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManager
   */
  protected $mailManager;

  /**
   * The url generator.
   *
   * @var Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
      // Load the service required to construct this class.
      $container->get('config.factory'),
      $container->get('current_user'),
      $container->get('plugin.manager.mail'),
      $container->get('url_generator')
    );
  }

  /**
   * Class constructor.
   */
  public function __construct(ConfigFactoryInterface $configFactory, AccountInterface $account, MailManager $mailManager, UrlGeneratorInterface $urlGenerator) {
    $this->configFactory = $configFactory;
    $this->account = $account;
    $this->mailManager = $mailManager;
    $this->urlGenerator = $urlGenerator;
  }

  /**
   * Implements send mail functionality.
   */
  public function sendMail($source = NULL, $type = NULL, $data = NULL) {
    $module = 'follow_unfollow';
    $key = $source;
    $from = $this->configFactory->get('system.site')->get('mail');
    // Follow and unfollow functionality the user will be login user.
    if ($source == 'follow' || $source == 'unfollow') {
      $to = $this->account->getEmail();
      $langcode = $this->account->getPreferredLangcode();
      $send = TRUE;
    }

    // Setting url and title of entity.
    if ($type == 'node') {
      $nid = $data->nid->getValue();
      $title = $data->title->getValue();
      $options = ['absolute' => TRUE];
      $url = $this->urlGenerator->generateFromRoute('entity.node.canonical', ['node' => $nid[0]['value']], $options);
      $title = '"' . $title[0]['value'] . '"';
    }
    elseif ($type == 'taxonomy') {
      $tid = $data->tid->getValue();
      $name = $data->name->getValue();
      $options = ['absolute' => TRUE];
      $url = $this->urlGenerator->generateFromRoute('entity.taxonomy_term.canonical', ['taxonomy' => $tid[0]['value']], $options);
      $title = '"' . $name[0]['value'] . '"';
    }
    elseif ($type == 'user') {
      $uid = $data->uid->getValue();
      $name = $data->name->getValue();
      $options = ['absolute' => TRUE];
      $url = $this->urlGenerator->generateFromRoute('entity.user.canonical', ['user' => $uid[0]['value']], $options);
      $title = '"' . $name[0]['value'] . '"';
    }

    // Params for email.
    $params = [
      '@username' => ucfirst($this->account->getUsername()),
      '@url' => isset($url) ? $url : '',
      '@title' => isset($title) ? $title : '',
    ];

    $this->mailManager->mail($module, $key, $to, $langcode, $params, $from, $send);
  }

}
