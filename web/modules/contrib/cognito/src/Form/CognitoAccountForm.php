<?php

namespace Drupal\cognito\Form;

use Drupal\cognito\Aws\CognitoInterface;
use Drupal\cognito\CognitoMessagesInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\externalauth\AuthmapInterface;
use Drupal\externalauth\ExternalAuthInterface;
use Drupal\user\AccountForm;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Base form for user forms that interact with Cognito.
 */
class CognitoAccountForm extends AccountForm {

  /**
   * The cognito service.
   *
   * @var \Drupal\cognito\Aws\Cognito
   */
  protected $cognito;

  /**
   * The cognito messages service.
   *
   * @var \Drupal\cognito\CognitoMessages
   */
  protected $cognitoMessages;

  /**
   * The external auth service.
   *
   * @var \Drupal\externalauth\ExternalAuthInterface
   */
  protected $externalAuth;

  /**
   * The authmap service.
   *
   * @var \Drupal\externalauth\AuthmapInterface
   */
  protected $authmap;

  /**
   * The eventDispatcher service.
   *
   * @var \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher
   */
  protected $eventDispatcher;

  /**
   * Constructs a new EntityForm object.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   The translation service.
   * @param \Drupal\cognito\Aws\CognitoInterface $cognito
   *   The cognito service.
   * @param \Drupal\cognito\CognitoMessagesInterface $cognitoMessages
   *   The cognito messages service.
   * @param \Drupal\externalauth\ExternalAuthInterface $externalAuth
   *   The external auth service.
   * @param \Drupal\externalauth\AuthmapInterface $authmap
   *   The authmap service.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, LanguageManagerInterface $language_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, TimeInterface $time = NULL, TranslationInterface $translation, CognitoInterface $cognito, CognitoMessagesInterface $cognitoMessages, ExternalAuthInterface $externalAuth, AuthmapInterface $authmap, EventDispatcherInterface $eventDispatcher) {
    parent::__construct($entity_repository, $language_manager, $entity_type_bundle_info, $time);
    $this->stringTranslation = $translation;
    $this->cognito = $cognito;
    $this->cognitoMessages = $cognitoMessages;
    $this->externalAuth = $externalAuth;
    $this->authmap = $authmap;
    $this->eventDispatcher = $eventDispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('language_manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('string_translation'),
      $container->get('cognito.aws'),
      $container->get('cognito.messages'),
      $container->get('externalauth.externalauth'),
      $container->get('externalauth.authmap'),
      $container->get('event_dispatcher')
    );
  }

}
