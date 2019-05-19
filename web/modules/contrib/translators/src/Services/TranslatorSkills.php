<?php

namespace Drupal\translators\Services;

use Drupal\Component\Utility\Html;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class TranslatorSkills.
 *
 * Translator skills helper class.
 *
 * @package Drupal\translators\Services
 */
class TranslatorSkills {
  use StringTranslationTrait;
  /**
   * The content translation manager.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;
  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;
  /**
   * Language skills field name.
   *
   * @var string
   */
  protected $translationSkillsField;
  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;
  /**
   * Current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request|null
   */
  protected $request;
  /**
   * Messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a TranslatorSkills service.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $stack
   *   Request stack.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Messenger.
   */
  public function __construct(
    AccountInterface $current_user,
    ConfigFactoryInterface $config_factory,
    EntityTypeManagerInterface $entity_type_manager,
    RequestStack $stack,
    MessengerInterface $messenger
  ) {
    $this->currentUser            = $current_user;
    $this->configFactory          = $config_factory;
    $this->entityTypeManager      = $entity_type_manager;
    $this->request                = $stack->getCurrentRequest();
    $this->messenger              = $messenger;
    $this->translationSkillsField = $this->configFactory
      ->get('translators.settings')
      ->get('translation_skills_field_name');
  }

  /**
   * Get skills array.
   *
   * @param \Drupal\Core\Session\AccountInterface|null $user
   *   User object. Optional.
   * @param bool $named
   *   Additional flag for building named multi-level array
   *   instead of languages list.
   *
   * @return array|bool
   *   Skills array.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getSkills($user = NULL, $named = FALSE) {
    if (!($user instanceof AccountInterface)) {
      $user = $this->currentUser;
    }
    $user_data = $this->userLoad($user->id());

    if (!isset($user_data->{$this->translationSkillsField})) {
      return FALSE;
    }

    $languages = [];
    foreach ($user_data->{$this->translationSkillsField}->getValue() as $skill) {

      if ($named) {
        $languages[] = $skill;
      }
      else {
        $languages = array_merge($languages, array_values($skill));
      }
    }
    return !$named ? array_unique($languages) : $languages;
  }

  /**
   * Get user's source skills.
   *
   * @param \Drupal\Core\Session\AccountInterface|null $user
   *   User object. Optional.
   *
   * @return array
   *   Array of the user's skills.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getSourceSkills($user = NULL) {
    return array_map(function ($skill) {
      return $skill['language_source'];
    }, $this->getSkills($user, TRUE));
  }

  /**
   * Check if user has a specified language skill.
   *
   * @param string $langcode
   *   Language ID.
   *
   * @return bool
   *   TRUE - if a specified language skill exists in users skills list,
   *   FALSE otherwise.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function hasSkill($langcode) {
    return (bool) in_array($langcode, $this->getSkills());
  }

  /**
   * Show empty message.
   *
   * @param string|int|null $user_id
   *   User ID.
   */
  public function showEmptyMessage($user_id = NULL) {
    $enabled = $this->configFactory
      ->get('translators.settings')
      ->get('enable_missing_skills_warning');
    // Prevent showing warning message
    // if the appropriate feature is not enabled.
    if (empty($enabled)) {
      return;
    }
    $user_id  = !$user_id ? $this->currentUser->id() : $user_id;
    $options  = $this->buildEditLinkOptions();
    $edit_url = Url::fromRoute('entity.user.edit_form', ['user' => $user_id], $options);
    $link     = Link::fromTextAndUrl('here', $edit_url)->toString();
    $message  = $this->t("Please register your translation skills @here", ['@here' => $link]);
    // Prevent duplicated warning messages on a page.
    $messages = $this->messenger->messagesByType('warning');
    if (empty($messages) || !in_array($message, $messages)) {
      $this->messenger->addWarning($message);
    }
  }

  /**
   * Build edit link options.
   *
   * @return array
   *   Edit link options array.
   */
  protected function buildEditLinkOptions() {
    $options = [];
    // Add fragment option to get user
    // automatically scrolled down to the needed field.
    $options['fragment'] = 'edit-' . Html::getId($this->translationSkillsField) . '-wrapper';
    // Add destination URL to get user being redirected back
    // to the correct page after saving the user's edit form.
    $options['query'] = [
      'destination' => $this->request->getRequestUri(),
    ];
    return $options;
  }

  /**
   * Load user entity by a given ID.
   *
   * @param int|string $id
   *   User ID.
   *
   * @return \Drupal\user\UserInterface|\Drupal\Core\Entity\EntityInterface|null
   *   Loaded user entity or NULL.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function userLoad($id) {
    $this->entityTypeManager->getStorage('user')->resetCache([$id]);
    return $this->entityTypeManager->getStorage('user')->load($id);
  }

}
