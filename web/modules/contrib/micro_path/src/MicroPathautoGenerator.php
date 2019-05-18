<?php

namespace Drupal\micro_path;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\pathauto\AliasTypeManager;
use Drupal\pathauto\MessengerInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Utility\Token;
use Drupal\pathauto\AliasCleanerInterface;
use Drupal\pathauto\AliasStorageHelperInterface;
use Drupal\pathauto\AliasUniquifierInterface;
use Drupal\pathauto\PathautoGenerator;
use Drupal\pathauto\PathautoGeneratorInterface;
use Drupal\pathauto\PathautoState;
use Drupal\token\TokenEntityMapperInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Provides methods for generating micro path aliases.
 */
class MicroPathautoGenerator extends PathautoGenerator implements MicroPathautoGeneratorInterface {

  /**
   * The site alias uniquifier.
   *
   * @var \Drupal\micro_path\SiteAliasUniquifierInterface
   */
  protected $siteAliasUniquifier;

  /**
   * Creates a new Micro Pathauto manager.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Utility\Token $token
   *   The token utility.
   * @param \Drupal\pathauto\AliasCleanerInterface $alias_cleaner
   *   The alias cleaner.
   * @param \Drupal\pathauto\AliasStorageHelperInterface $alias_storage_helper
   *   The alias storage helper.
   * @param AliasUniquifierInterface $alias_uniquifier
   *   The alias uniquifier.
   * @param \Drupal\pathauto\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   * @param \Drupal\token\TokenEntityMapperInterface $token_entity_mappper
   *   The token entity mapper service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager
   * @param \Drupal\pathauto\AliasTypeManager $alias_type_manager
   *   Manages pathauto alias type plugins.
   * @param \Drupal\micro_path\SiteAliasUniquifierInterface $site_alias_uniquifier
   *   The site alias uniquifier.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler, Token $token, AliasCleanerInterface $alias_cleaner, AliasStorageHelperInterface $alias_storage_helper, AliasUniquifierInterface $alias_uniquifier, MessengerInterface $messenger, TranslationInterface $string_translation, TokenEntityMapperInterface $token_entity_mappper, EntityTypeManagerInterface $entity_type_manager, AliasTypeManager $alias_type_manager, SiteAliasUniquifierInterface $site_alias_uniquifier) {
    parent::__construct($config_factory, $module_handler, $token, $alias_cleaner, $alias_storage_helper, $alias_uniquifier, $messenger, $string_translation, $token_entity_mappper, $entity_type_manager, $alias_type_manager);
    $this->siteAliasUniquifier = $site_alias_uniquifier;
  }

  /**
   * {@inheritdoc}
   */
  public function createEntitySiteAlias(EntityInterface $entity, $site_id, $op = 'micro_path') {
    // Retrieve and apply the pattern for this content type.
    $pattern = $this->getPatternByEntity($entity);
    if (empty($pattern)) {
      // No pattern? Do nothing (otherwise we may blow away existing aliases...)
      return NULL;
    }

    $source = '/' . $entity->toUrl()->getInternalPath();
    $config = $this->configFactory->get('pathauto.settings');
    $langcode = $entity->language()->getId();

    // Core does not handle aliases with language Not Applicable.
    if ($langcode == LanguageInterface::LANGCODE_NOT_APPLICABLE) {
      $langcode = LanguageInterface::LANGCODE_NOT_SPECIFIED;
    }

    // Build token data.
    $data = [
      $this->tokenEntityMapper->getTokenTypeForEntityType($entity->getEntityTypeId()) => $entity,
    ];

    // Allow other modules to alter the pattern.
    $context = array(
      'module' => $entity->getEntityType()->getProvider(),
      'entity_type' => $entity->getEntityTypeId(),
      'site_id' => $site_id,
      'source' => $source,
      'data' => $data,
      'bundle' => $entity->bundle(),
      'language' => &$langcode,
    );

    $pattern_original = $pattern->getPattern();
    $this->moduleHandler->alter('micro_path_pattern', $pattern, $context);
    $pattern_altered = $pattern->getPattern();

    // Special handling when updating an item which is already aliased.
    $existing_micro_path = NULL;
    $microPathStorage = $this->entityTypeManager->getStorage('micro_path');
    $properties = [
      'source' => $source,
      'language' => $langcode,
      'site_id' => $site_id,
    ];
    $existing_micro_path = $microPathStorage->loadByProperties($properties);
    if ($existing_micro_path) {
      $existing_micro_path = reset($existing_micro_path);
      switch ($config->get('update_action')) {
        case PathautoGeneratorInterface::UPDATE_ACTION_NO_NEW:
          // If an alias already exists,
          // and the update action is set to do nothing,
          // then gosh-darn it, do nothing.
          return NULL;
      }
    }

    // Replace any tokens in the pattern.
    // Uses callback option to clean replacements. No sanitization.
    // Pass empty BubbleableMetadata object to explicitly ignore cacheablity,
    // as the result is never rendered.
    $alias = $this->token->replace($pattern->getPattern(), $data, array(
      'clear' => TRUE,
      'callback' => array($this->aliasCleaner, 'cleanTokenValues'),
      'langcode' => $langcode,
      'pathauto' => TRUE,
    ), new BubbleableMetadata());

    // Check if the token replacement has not actually replaced any values. If
    // that is the case, then stop because we should not generate an alias.
    // @see token_scan()
    $pattern_tokens_removed = preg_replace('/\[[^\s\]:]*:[^\s\]]*\]/', '', $pattern->getPattern());
    if ($alias === $pattern_tokens_removed) {
      return NULL;
    }

    $alias = $this->aliasCleaner->cleanAlias($alias);

    // Allow other modules to alter the alias.
    $context['source'] = &$source;
    $context['pattern'] = $pattern;
    $this->moduleHandler->alter('micro_path_alias', $alias, $context);

    // If we have arrived at an empty string, discontinue.
    if (!Unicode::strlen($alias)) {
      return NULL;
    }

    // If the alias already exists, generate a new, hopefully unique, variant.
    $original_alias = $alias;
    $this->siteAliasUniquifier->uniquify($alias, $source, $site_id, $langcode);
    if ($original_alias != $alias) {
      // Alert the user why this happened.
      $this->messenger->addMessage($this->t('The automatically generated alias %original_alias conflicted with an existing alias. Alias changed to %alias.', array(
        '%original_alias' => $original_alias,
        '%alias' => $alias,
      )), $op);
    }

    if ($op == 'update' && $existing_micro_path instanceof MicroPathInterface) {
      $existing_micro_path->set('alias', $alias);
      $existing_micro_path->save();
    }

    if ($pattern_altered !== $pattern_original) {
      $pattern->setPattern($pattern_original);
    }

    return $alias;
  }

  /**
   * {@inheritdoc}
   */
  public function updateEntitySiteAlias(EntityInterface $entity, $site_id, $op = 'micro_path', array $options = array()) {
    // Skip if the entity does not have the path field.
    if (!($entity instanceof ContentEntityInterface) || !$entity->hasField('path')) {
      return NULL;
    }

    // Skip if pathauto processing is disabled.
    if ($entity->path->pathauto != PathautoState::CREATE && empty($options['force'])) {
      return NULL;
    }

    // Only act if this is the default revision.
    if ($entity instanceof RevisionableInterface && !$entity->isDefaultRevision()) {
      return NULL;
    }

    $options += array('language' => $entity->language()->getId());
    $type = $entity->getEntityTypeId();

    // Skip processing if the entity has no pattern.
    if (!$this->getPatternByEntity($entity)) {
      return NULL;
    }

    // Deal with taxonomy specific logic.
    // @todo Update and test forum related code.
    if ($type == 'taxonomy_term') {

      $config_forum = $this->configFactory->get('forum.settings');
      if ($entity->getVocabularyId() == $config_forum->get('vocabulary')) {
        $type = 'forum';
      }
    }

    try {
      $result = $this->createEntitySiteAlias($entity, $site_id, $op);
    }
    catch (\InvalidArgumentException $e) {
      drupal_set_message($e->getMessage(), 'error');
      return NULL;
    }

    // @todo Move this to a method on the pattern plugin.
    if ($type == 'taxonomy_term') {
      foreach ($this->loadTermChildren($entity->id()) as $subterm) {
        $this->updateEntitySiteAlias($subterm, $site_id, $op, $options);
      }
    }

    return $result;
  }
}
