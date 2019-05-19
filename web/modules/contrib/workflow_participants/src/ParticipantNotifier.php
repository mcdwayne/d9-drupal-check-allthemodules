<?php

namespace Drupal\workflow_participants;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Utility\Token;
use Drupal\token\TokenEntityMapperInterface;
use Drupal\workflow_participants\Entity\WorkflowParticipantsInterface;

/**
 * Participant notificer service.
 */
class ParticipantNotifier implements ParticipantNotifierInterface {

  /**
   * Configuration factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mail;

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * The optional token entity mapper.
   *
   * @var \Drupal\token\TokenEntityMapperInterface
   */
  protected $tokenEntityMapper;

  /**
   * Constructs the participant notifier service.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The config factory service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail manager service.
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   * @param \Drupal\token\TokenEntityMapperInterface $entity_mapper
   *   The optional entity mapper service if the token contrib module is
   *   available.
   */
  public function __construct(ConfigFactoryInterface $config, EntityTypeManagerInterface $entity_type_manager, MailManagerInterface $mail_manager, Token $token, TokenEntityMapperInterface $entity_mapper = NULL) {
    $this->configFactory = $config;
    $this->entityTypeManager = $entity_type_manager;
    $this->mail = $mail_manager;
    $this->token = $token;
    $this->tokenEntityMapper = $entity_mapper;
  }

  /**
   * {@inheritdoc}
   */
  public function getNewParticipants(WorkflowParticipantsInterface $participants) {
    $new_participants = [];

    if (isset($participants->original)) {
      // This is being updated, diff the participants arrays.
      /** @var \Drupal\workflow_participants\Entity\WorkflowParticipantsInterface $old */
      $old = $participants->original;
      $new_participants += array_diff_key($participants->getEditors(), $old->getEditors());
      $new_participants += array_diff_key($participants->getReviewers(), $old->getReviewers());
    }
    else {
      // They are all new!
      $new_participants = $participants->getReviewers() + $participants->getEditors();
    }

    ksort($new_participants);
    return $new_participants;
  }

  /**
   * {@inheritdoc}
   */
  public function processNotifications(WorkflowParticipantsInterface $participants) {
    if ($this->configFactory->get('workflow_participants.settings')->get('enable_notifications')) {
      $this->sendNotifications($this->getNewParticipants($participants), $participants->getModeratedEntity());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function sendNotifications(array $accounts, EntityInterface $entity) {
    $params = [
      'moderated_entity' => $entity,
    ];
    $config = $this->configFactory->get('workflow_participants.settings');
    $subject = $config->get('participant_message.subject');
    $body = $config->get('participant_message.body.value');
    $format = $config->get('participant_message.body.format');
    $context = $this->getTokenContext($entity);

    foreach ($accounts as $account) {
      $params['account'] = $account;
      $context['user'] = $account;

      $params['subject'] = $this->token->replace($subject, $context);
      $params['body'] = check_markup($this->token->replace($body, $context), $format);

      $this->mail->mail('workflow_participants', 'new_participant', $account->getEmail(), $account->getPreferredLangcode(), $params);
    }
  }

  /**
   * Get the token context.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity the participant has been added to.
   *
   * @return array
   *   An array to be used as data for token replacement.
   */
  protected function getTokenContext(EntityInterface $entity) {
    if ($this->tokenEntityMapper) {
      $context = [
        'entity' => $entity,
        $this->tokenEntityMapper->getTokenTypeForEntityType($entity->getEntityTypeId(), TRUE) => $entity,
      ];
    }
    else {
      $context = [
        'entity' => $entity,
        $entity->getEntityTypeId() => $entity,
      ];
    }
    return $context;
  }

}
