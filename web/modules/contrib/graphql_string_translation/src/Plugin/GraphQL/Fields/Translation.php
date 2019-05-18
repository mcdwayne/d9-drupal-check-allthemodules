<?php

namespace Drupal\graphql_string_translation\Plugin\GraphQL\Fields;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\graphql\GraphQL\Execution\ResolveContext;
use Drupal\graphql\Plugin\GraphQL\Fields\FieldPluginBase;
use Drupal\locale\StringStorageInterface;
use GraphQL\Type\Definition\ResolveInfo;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides translations for a given string in given language.
 *
 * @GraphQLField(
 *   id = "translation",
 *   name = "translation",
 *   type = "String",
 *   multi = false,
 *   nullable = false,
 *   secure = true,
 *   response_cache_tags = { "locale" },
 *   arguments={
 *    "text" = "String",
 *    "language" = "LanguageId!"
 *   }
 * )
 */
class Translation extends FieldPluginBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;
  use DependencySerializationTrait;

  /**
   * @var \Drupal\locale\StringStorageInterface
   */
  protected $localeStorage;

  /**
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Translation constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\locale\StringStorageInterface $localeStorage
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, StringStorageInterface $localeStorage, AccountProxyInterface $currentUser, LoggerChannelInterface $logger, LanguageManagerInterface $languageManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->localeStorage = $localeStorage;
    $this->currentUser = $currentUser;
    $this->logger = $logger;
    $this->languageManager = $languageManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('locale.storage'),
      $container->get('current_user'),
      $container->get('logger.factory')->get('graphql_string_translation'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function resolveValues($value, array $args, ResolveContext $context, ResolveInfo $info) {
    $text = $args['text'];
    $langcode = $args['language'];

    yield $this->getTranslation($text, $langcode);
  }

  /**
   * Attempts to translate the string and returns the result.
   *
   * @param $text
   * @param null $langcode
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|\Drupal\locale\SourceString|null
   */
  public function getTranslation($text, $langcode = NULL) {
    if (is_null($langcode)) {
      $langcode = $this->languageManager->getCurrentLanguage()->getId();
    }

    $options = [
      'langcode' => $langcode,
      'context' => 'graphql',
    ];
    $userIsPrivileged = $this
      ->currentUser
      ->hasPermission('request translation of arbitrary strings');

    if ($userIsPrivileged) {
      return $this->t($text, [], $options);
    } else {
      $string = $this->localeStorage->findString([
        'source' => $text,
        'context' => 'graphql',
      ]);

      if (is_null($string)) {
        $replacements = [
          '%m' => $text,
        ];
        $this->logger->warning($this->t('The string %s was not found in graphql context. Add it at /admin/config/graphql/string-translation.', $replacements));
        return $string;
      } else {
        return $this->t($text, [], $options);
      }
    }
  }

}
