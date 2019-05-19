<?php

namespace Drupal\workflow_participants;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EditorialContentEntityBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Token generation class.
 */
class Tokens implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs the token generator object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity_type.manager'));
  }

  /**
   * Add workflow participant tokens.
   */
  public function infoAlter(&$info) {
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type) {
      if ($entity_type->entityClassImplements(EditorialContentEntityBase::class)) {
        $info['tokens'][$entity_type->id()]['editors'] = [
          'name' => $this->t('Editors'),
          'description' => $this->t('Editors for this @type', ['@type' => $entity_type->getLabel()]),
        ];
        $info['tokens'][$entity_type->id()]['reviewers'] = [
          'name' => $this->t('Reviewers'),
          'description' => $this->t('Reviewers for this @type', ['@type' => $entity_type->getLabel()]),
        ];
        $info['tokens'][$entity_type->id()]['all-participants'] = [
          'name' => $this->t('All workflow participants'),
          'description' => $this->t('Editors and reviewers for this @type', ['@type' => $entity_type->getLabel()]),
        ];
        $info['tokens'][$entity_type->id()]['participant-type'] = [
          'name' => $this->t('Workflow participant type'),
          'description' => $this->t('Participant type for this @type', ['@type' => $entity_type->getLabel()]),
        ];
      }
    }
  }

  /**
   * Generates token replacements.
   *
   * @param string $type
   *   The machine-readable name of the type (group) of token being replaced,
   *   such as 'node', 'user', or another type defined by a hook_token_info()
   *   implementation.
   * @param array $tokens
   *   An array of tokens to be replaced. The keys are the machine-readable
   *   token names, and the values are the raw [type:token] strings that
   *   appeared in the original text.
   * @param array $data
   *   An associative array of data objects to be used when generating
   *   replacement values, as supplied in the $data parameter to
   *   \Drupal\Core\Utility\Token::replace().
   * @param array $options
   *   An associative array of options for token replacement; see
   *   \Drupal\Core\Utility\Token::replace() for possible values.
   * @param \Drupal\Core\Render\BubbleableMetadata $bubbleable_metadata
   *   Bubbleable metadata.
   *
   * @return array
   *   An associative array of replacement values, keyed by the raw [type:token]
   *   strings from the original text. The returned values must be either plain
   *   text strings, or an object implementing MarkupInterface if they are
   *   HTML-formatted.
   *
   * @see \workflow_participants_token_info()
   */
  public function getTokens($type, array $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
    $replacements = [];
    if (isset($data[$type]) && $data[$type] instanceof EditorialContentEntityBase && $data[$type]->id()) {
      /** @var \Drupal\workflow_participants\WorkflowParticipantsStorageInterface $storage */
      $storage = $this->entityTypeManager->getStorage('workflow_participants');

      foreach ($tokens as $name => $original) {
        // Participants are only loaded if any relevant tokens are used.
        switch ($name) {
          case 'editors':
            $participants = $storage->loadForModeratedEntity($data[$type]);
            if (!empty($participants->getEditors())) {
              $replacements[$original] = $this->formatParticipants($participants->getEditors(), $bubbleable_metadata);
              $bubbleable_metadata->addCacheableDependency($participants);
            }
            break;

          case 'reviewers':
            $participants = $storage->loadForModeratedEntity($data[$type]);
            if (!empty($participants->getReviewers())) {
              $replacements[$original] = $this->formatParticipants($participants->getReviewers(), $bubbleable_metadata);
              $bubbleable_metadata->addCacheableDependency($participants);
            }
            break;

          case 'all-participants':
            $participants = $storage->loadForModeratedEntity($data[$type]);
            if (!empty($participants->getEditors()) || !empty($participants->getReviewers())) {
              $replacements[$original] = $this->formatParticipants($participants->getEditors() + $participants->getReviewers(), $bubbleable_metadata);
              $bubbleable_metadata->addCacheableDependency($participants);
            }
            break;

          case 'participant-type':
            $participants = $storage->loadForModeratedEntity($data[$type]);
            if (isset($data['user']) && $participants->isEditor($data['user'])) {
              $replacements[$original] = $this->t('Editor');
              $bubbleable_metadata->addCacheableDependency($participants);
              $bubbleable_metadata->addCacheableDependency($data['user']);
            }
            elseif (isset($data['user']) && $participants->isReviewer($data['user'])) {
              $replacements[$original] = $this->t('Reviewer');
              $bubbleable_metadata->addCacheableDependency($participants);
              $bubbleable_metadata->addCacheableDependency($data['user']);
            }
            break;
        }
      }
    }

    return $replacements;
  }

  /**
   * Helper function to format a list of users.
   *
   * @param \Drupal\user\UserInterface[] $accounts
   *   List of accounts to format.
   * @param \Drupal\Core\Render\BubbleableMetadata $bubbleable_metadata
   *   The bubbleable metadata object is passed so any user accounts can be
   *   added.
   *
   * @return string
   *   A comma-separated list of usernames.
   */
  protected function formatParticipants(array $accounts, BubbleableMetadata $bubbleable_metadata) {
    $formatted = [];

    foreach ($accounts as $account) {
      $formatted[] = $account->getDisplayName();
      $bubbleable_metadata->addCacheableDependency($account);
    }

    $formatted = array_unique($formatted);
    asort($formatted);
    return implode(', ', $formatted);
  }

}
