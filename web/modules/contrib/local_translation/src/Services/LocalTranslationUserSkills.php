<?php

namespace Drupal\local_translation\Services;

use Drupal\Component\Utility\Html;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\user\Entity\User;

/**
 * Class LocalTranslationUserSkills.
 *
 * User translation skills helper class.
 *
 * @package Drupal\local_translation\Services
 */
class LocalTranslationUserSkills {
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
  protected $fieldName;

  /**
   * Constructs a LocalTranslationUserSkills service.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory.
   */
  public function __construct(AccountInterface $current_user, ConfigFactoryInterface $config_factory) {
    $this->currentUser   = $current_user;
    $this->configFactory = $config_factory;
    $this->fieldName     = $this->configFactory
      ->get('local_translation.settings')
      ->get('field_name');
  }

  /**
   * Add multiple skills.
   *
   * @param array $skills
   *   Array of arrays of from->to skills.
   * @param null|\Drupal\user\Entity\User $user
   *   User to operate on.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function addSkills(array $skills, $user = NULL) {
    if (is_null($user)) {
      $user = User::load($this->currentUser->id());
    }
    foreach ($skills as $skill) {
      $this->addSkill($skill, $user);
    }
  }

  /**
   * Add translation skill.
   *
   * @param array $skill
   *   Array of from->to skills.
   * @param null|\Drupal\user\Entity\User $user
   *   User to operate on.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function addSkill(array $skill, $user = NULL) {
    if (is_null($user)) {
      $user = User::load($this->currentUser->id());
    }
    $user->get($this->fieldName)->appendItem([
      'source_languages' => $skill[0],
      'target_languages' => $skill[1],
    ]);
    $user->save();
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
   */
  public function getSkills($user = NULL, $named = FALSE) {
    if (!($user instanceof AccountInterface)) {
      $user = $this->currentUser;
    }
    $user_data = User::load($user->id());

    if (!isset($user_data->{$this->fieldName})) {
      return FALSE;
    }

    $languages = [];
    foreach ($user_data->{$this->fieldName}->getValue() as $skill) {
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
   * Get user's "from"/source skills.
   *
   * @param \Drupal\Core\Session\AccountInterface|null $user
   *   User object. Optional.
   *
   * @return array
   *   Array of the user's skills.
   */
  public function getSourceSkills($user = NULL) {
    return array_map(function ($skill) {
      return $skill['language_from'];
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
   */
  public function userHasSkill($langcode) {
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
      ->get('local_translation.settings')
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
    $messages = drupal_get_messages('warning');
    if (empty($messages) || !in_array($message, $messages)) {
      drupal_set_message($message, 'warning');
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
    $options['fragment'] = 'edit-' . Html::getId($this->fieldName) . '-wrapper';
    // Add destination URL to get user being redirected back
    // to the correct page after saving the user's edit form.
    $options['query'] = [
      'destination' => \Drupal::request()->getRequestUri(),
    ];
    return $options;
  }

}
