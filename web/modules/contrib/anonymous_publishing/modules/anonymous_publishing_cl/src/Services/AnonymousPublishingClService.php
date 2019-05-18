<?php

namespace Drupal\anonymous_publishing_cl\Services;

use Drupal\Component\Utility\Html;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Condition;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Flood\FloodInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Routing\UrlGeneratorTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Helper methods for Anonymous Publishing CL.
 */
class AnonymousPublishingClService {
  use StringTranslationTrait;
  use UrlGeneratorTrait;

  /**
   * The config factory.
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The logger factory.
   *
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The database connection service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The flood verification service
   *
   * @var \Drupal\Core\Flood\FloodInterface
   */
  protected $flood;

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * Constructs a MasonryService object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface
   *   The config factory service
   * @param \Drupal\Core\Logger\LoggerChannelInterface
   *   The logger factory service
   * @param \Drupal\Core\Database\Connection
   *   The database connection service
   * @param \Symfony\Component\HttpFoundation\RequestStack
   *  The request stack
   * @param Drupal\Core\Flood\FloodInterface
   *   The flood verification service
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail manager.
   */
  function __construct(ConfigFactory $config_factory, LoggerChannelFactoryInterface $logger_factory, Connection $connection, RequestStack $request_stack, FloodInterface $flood, MailManagerInterface $mail_manager) {
    $this->configFactory = $config_factory;
    $this->logger = $logger_factory->get('anonymous_publishing');
    $this->database = $connection;
    $this->request = $request_stack->getCurrentRequest();
    $this->flood = $flood;
    $this->mailManager = $mail_manager;
  }

  /**
   * Check if the content type can be published anonymously.
   *
   * @param string $type
   *   The content type to check.
   *
   * @return bool
   *   TRUE if the content type can be published anonymously,
   *   FALSE otherwise.
   */
  public function isContentTypeAllowed($type) {
    $types = $this->configFactory->get('anonymous_publishing_cl.settings')
      ->get('allowed_content_types');
    return (isset($types) && !empty($types[$type]));
  }

  /**
   * Send verification email.
   *
   * @param object $entity
   *   The node object.
   * @param string $akey
   *   Activation key.
   */
  public function sendVerificationMail(EntityInterface $entity, $akey) {
    $settings = $this->configFactory->get('anonymous_publishing_cl.settings');
    $emailSettings = $this->configFactory->get('anonymous_publishing_cl.mail');
    $options = $settings->get('general_options');

    // Get the entity title and link.
    if ($entity->getEntityTypeId() == 'node') {
      $title = $entity->getTitle();
      $vfurl = Url::fromRoute('anonymous_publishing_cl.verify', array(), array(
        'query' => array('akey' => $akey),
        'absolute' => TRUE
      ));
      $modp = !$options['sactivate'];
    }
    else {
      if ($entity->getEntityTypeId() == 'comment') {
        $title = $entity->getSubject();
        if (empty($title)) {
          $title = '';
        }
        $vfurl = Url::fromRoute('anonymous_publishing_cl.verify', array(), array(
          'query' => array('akey' => $akey),
          'absolute' => TRUE
        ));
        $modp = !$options['sactivate'];
      }
    }

    // Build emails remplacement tokens values.
    $autodelhours = $settings->get('autodelhours');
    $variables = array(
      '@action' => $modp ? t('verify') : t('activate'),
      '@autodelhours' => $settings->get('autodelhours'),
      '@email' => $entity->anonymous_publishing_email,
      '@site' => $this->configFactory->get('system.site')->get('name'),
      '@title' => Html::escape($title),
      '@verification_uri' => $vfurl->toString(),
    );

    // Build verification mail.
    $to = $entity->anonymous_publishing_email;
    $subject = $modp ? $this->t($emailSettings->get('email_subject_verify'), $variables) : $this->t($emailSettings->get('email_subject_active'), $variables);
    $b1 = $this->t($emailSettings->get('email_introduction'), $variables);
    $b2 = $autodelhours >= 0 ? $this->t($emailSettings->get('email_activate'), $variables) : '';
    $b3 = $modp ? $this->t($emailSettings->get('email_verify'), $variables) : '';
    $body = array($b1, $b2, $b3);
    $params = array(
      'subject' => $subject,
      'body' => $body,
    );

    // Send verification mail
    $result = $this->mailManager->mail('anonymous_publishing_cl', 'verify', $to, \Drupal::currentUser()
      ->getPreferredLangcode(), $params);
    if ($result['result'] == TRUE) {
      drupal_set_message(t('A link and further instructions have been sent to your e-mail address.'));
      $this->logger->notice('Verification mail sent to @to', array(
        '@to' => $to,
      ));
    }
    else {
      $this->logger->error('Error mailing activation/verification link.');
      drupal_set_message(t('Unable to send mail. Please contact the site admin.'), 'error');
    }

    // Send admin notification mail.
    if ($options['modmail']) {
      $subject = $this->t($emailSettings->get('email_admin_subject'), $variables);
      $body = array($this->t($emailSettings->get('email_admin_body'), $variables));
      $to = $settings->get('notification_email_destination');
      $params = array(
        'subject' => $subject,
        'body' => $body,
      );
      $result = $this->mailManager->mail('anonymous_publishing_cl', 'verify', $to, \Drupal::currentUser()
        ->getPreferredLangcode(), $params);
      if ($result['result'] == FALSE) {
        $this->logger->error('Error notifying admin.');
      }
    }
  }

  /**
   * Verify that the given entity actually needs validation against anonymous
   * posting.
   *
   * @param $content
   *  The content to validate.
   * @return bool
   *   TRUE if content should be handled, else FALSE.
   */
  protected function isValidationNeeded($form) {
    if (\Drupal::currentUser()->isAuthenticated()) {
      return FALSE;
    }
    return isset($form['anonymous_publishing']['email']);
  }

  /**
   * Common validation of submission form for nodes and comments.
   *
   * Only called when content is first posted (not when it it is
   * activated via link).
   *
   * @param mixed $entity
   *   The entity to validate (node or comment).
   *
   * @return bool
   *   TRUE if the form validates, FALSE otherwise.
   */
  public function validate(&$form, FormStateInterface $form_state) {
    $settings = $this->configFactory->get('anonymous_publishing_cl.settings');

    // Check if validation is needed here.
    $need_validation = $this->isValidationNeeded($form);
    if (!$need_validation) {
      return TRUE;
    }

    // Extract email and email_confirm_field values
    $email = $form_state->getValue('anonymous_publishing_email');
    $email_spam_bot = $form_state->getValue('anonymous_publishing_email_confirm_field');

    // Check and act if spam bot detected.
    if (!empty($email_spam_bot)) {

      // Log the spam bot.
      $this->logger->warning('Bot with email "@email".', array('@email' => $email));

      // Insert or update the bot submission in DB.
      $query = $this->database->select('anonymous_publishing_bots');
      $query->fields('anonymous_publishing_bots', array('id'));
      $query->condition('ip', $this->request->getClientIp());
      $id = $query->execute()->fetchField();

      if ($id) {
        $this->database->update('anonymous_publishing_bots')
          ->fields(array(
            'last' => REQUEST_TIME,
          ))
          ->expression('visits', 'visits + 1')
          ->condition('id', $id)
          ->execute();
      } else {
        $this->database->insert('anonymous_publishing_bots')->fields(
          array('ip', 'visits', 'first', 'last'),
          array($this->request->getClientIp(), 1, REQUEST_TIME, REQUEST_TIME,)
        )->execute();
      }

      $form_state->setErrorByName(anonymous_publishing_email, "I smell a bot.  Please log in to post.");
      return $this->redirect('<front>');
    }

    // If the entity is newly inserted (ie not edition).
    $nid = $form_state->getFormObject()->getEntity()->id();

    if (empty($nid)) {

      // If email given if for registered user, and if this is not permitted:
      // Do not authorize.
      user_load_by_mail($email);
      $options = $settings->get('general_options');
      if (user_load_by_mail($email) && !$options['aregist']) {
        $form_state->setErrorByName('anonymous_publishing_email', t('This e-mail is already in use.  If this is you, please log in to post.'));
        return FALSE;
      }

      // Retrieve all previous submissions made with this email address.
      $ip = $this->request->getClientIp();
      $query = $this->database->select('anonymous_publishing_emails');
      $query->fields('anonymous_publishing_emails');
      $query->condition('email', $email);

      if ($options['blockip']) {
        $condition = new Condition('OR');
        $condition->condition('ipaddress', $ip);
        $query->condition($condition);
      }
      $result = $query->execute()->fetchAll();
      $num_prev_submissions = count($result);

      // Block if at least one record indicate that this should be blocked.
      $blocked = 0;
      $now = date('Y-m-d');
      if ($num_prev_submissions) {
        foreach ($result as $record) {
          $auid = $record->auid;
          $blocked += $record->blocked;
          $this->database->update('anonymous_publishing_emails')
            ->fields(array('lastseen' => $now))
            ->condition('auid', $auid)
            ->execute();
        }
      }
      else {
        // Block is this post is considered as flooding.
        $flood_limit = $settings->get('flood_limit');
        $flooded = FALSE;
        if ($flood_limit != -1) {
          $blockingByIp = $settings->get('blockip');
          if ($blockingByIp) {
            if (!$this->flood->isAllowed('anonymous_publishing_ip', $flood_limit, 3600)) {
              $flooded = TRUE;
            }
          } else if (!$this->flood->isAllowed('anonymous_publishing_em', $flood_limit, 3600, $email)) {
            $flooded = TRUE;
          }
          $this->flood->register('anonymous_publishing_ip', 3600);
          $this->flood->register('anonymous_publishing_em', 3600, $email);
        }

        // Check if the post is a flood.
        if ($flooded) {
          $form_state->setErrorByName('anonymous_publishing_email', t('This website only allows @flood_limit postings of content from non-registered users within one hour.  This restriction may be lifted if you register.', array('@flood_limit' => $flood_limit)));
          return FALSE;
        }
        return FALSE;
      }

      // Check if the IP is banned.
      if ($blocked) {
        $form_state->setErrorByName('anonymous_publishing_email', t('This e-mail/ip-address is banned from posting content on this site.  Please contact the site administrator if you believe this is an error.'));
        return FALSE;
      }
    }
    return TRUE;
  }
}