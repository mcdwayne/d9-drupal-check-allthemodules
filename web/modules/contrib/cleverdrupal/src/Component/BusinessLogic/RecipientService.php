<?php

namespace Drupal\cleverreach\Component\BusinessLogic;

use CleverReach\BusinessLogic\Entity\Recipient;
use CleverReach\BusinessLogic\Entity\Tag;
use CleverReach\BusinessLogic\Entity\TagCollection;
use CleverReach\BusinessLogic\Interfaces\Recipients;
use CleverReach\Infrastructure\Interfaces\Required\Configuration;
use CleverReach\Infrastructure\ServiceRegister;
use Drupal;
use Drupal\cleverreach\Component\Repository\UserRepository;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\Entity\User;

/**
 * Prepares recipients for export to CleverReach.
 */
class RecipientService implements Recipients {
  const TAG_TYPE_ROLE = 'Role';
  const TAG_TYPE_SITE = 'Site';
  const TAG_TYPE_TAXONOMY = 'Taxonomy';

  /**
   * Instance of Configuration class.
   *
   * @var \CleverReach\Infrastructure\Interfaces\Required\Configuration
   */
  private $configService;
  /**
   * Instance of UserRepository class.
   *
   * @var \Drupal\cleverreach\Component\Repository\UserRepository
   */
  private $userRepository;
  /**
   * Cached list of website tags.
   *
   * @var array
   */
  private $websites = [];
  /**
   * Cached site name.
   *
   * @var string
   */
  private $siteName = '';
  /**
   * Cached list of user role tags.
   *
   * @var array
   */
  private $userRoles = [];

  /**
   * @inheritdoc
   */
  public function getAllTags() {
    $tag = $this->getTagsFormatted(
        $this->getUserRoles(),
        self::TAG_TYPE_ROLE
    );
    $tag->add(
        $this->getTagsFormatted(
            $this->getWebsites(),
            self::TAG_TYPE_SITE
        )
    );
    $tag->add(
        $this->getTagsFormatted(
            $this->getTaxonomies(),
            self::TAG_TYPE_TAXONOMY
        )
    );
    return $tag;
  }

  /**
   * @inheritdoc
   */
  public function getRecipientsWithTags(
        array $batchRecipientIds,
        $includeOrders
    ) {
    $result = [];

    if (empty($batchRecipientIds)) {
      return $result;
    }

    $users = $this->getUserRepository()->get(
        [
            [
              'field' => 'uid',
              'value' => $batchRecipientIds,
              'condition' => 'IN',
            ],
        ]
    );

    /** @var \Drupal\user\Entity\User $user */
    foreach ($batchRecipientIds as $recipientId) {
      $user = isset($users[(int) $recipientId]) ?
                $users[(int) $recipientId] : NULL;

      if ($user === NULL && !$this->isEmail($recipientId)) {
        continue;
      }

      // When email address is provided as batch recipient id, user
      // should be deactivated on CleverReach side.
      if ($this->isEmail($recipientId)) {
        $result[] = $this->createInactiveRecipient($recipientId);
      }
      else {
        /** @var \Drupal\Core\Field\FieldItemList $field */
        foreach ($user->getFields(FALSE) as $code => $field) {
          if (!$user->hasField($code) || $field->getFieldDefinition()
            ->getType() !== 'email') {
            continue;
          }

          // Always skip address saved as initial user email address.
          if ($code === 'init') {
            continue;
          }

          foreach ($user->get($code)->getValue() as $value) {
            if (empty($value['value'])) {
              continue;
            }

            $result[] = $this->createRecipientFromUser(
                $user,
                $value['value']
            );
          }
        }
      }
    }

    return $result;
  }

  /**
   * @inheritdoc
   */
  public function getAllRecipientsIds() {
    return $this->getUserRepository()->getAllIds();
  }

  /**
   * @inheritdoc
   */
  public function recipientSyncCompleted(array $recipientIds) {
    // Intentionally left empty. We do not need this functionality.
  }

  /**
   * Gets formatted tag collection.
   *
   * @param array $sourceTags
   *   Array of tags.
   * @param string $type
   *   Tag type.
   *
   * @return \CleverReach\BusinessLogic\Entity\TagCollection
   *   Collection of Tag objects.
   */
  private function getTagsFormatted(array $sourceTags, $type) {
    $tagCollection = new TagCollection();
    foreach ($sourceTags as $sourceTag) {
      $tag = new Tag($sourceTag, $type);
      $tagCollection->addTag($tag);
    }
    return $tagCollection;
  }

  /**
   * Creates recipient object that should be deactivated on CleverReach side.
   *
   * @param string $email
   *   Email of recipient that needs to be deleted.
   *
   * @return \CleverReach\BusinessLogic\Entity\Recipient
   *   Recipient object.
   */
  private function createInactiveRecipient($email) {
    $recipient = new Recipient($email);
    $recipient->setActive(FALSE);
    $recipient->setNewsletterSubscription(FALSE);
    return $recipient;
  }

  /**
   * Creates recipient object from user object.
   *
   * @param \Drupal\user\Entity\User $user
   *   User entity.
   * @param string|null $email
   *   Email of user.
   *
   * @return \CleverReach\BusinessLogic\Entity\Recipient
   *   Recipient object.
   */
  private function createRecipientFromUser(User $user, $email = NULL) {
    $recipient = new Recipient(
        $email === NULL ? $user->getEmail() : $email
    );

    $date = new \DateTime();
    $date->setTimestamp($user->getCreatedTime());

    if (NULL !== $date) {
      $recipient->setActivated($date);
      $recipient->setRegistered($date);
    }

    $isSubscribed = $this->isSubscribed($user);
    $recipient->setCustomerNumber($user->id());
    $recipient->setFirstName($user->getUsername());
    $recipient->setLanguage($user->getPreferredLangcode());
    $recipient->setActive($isSubscribed);
    $recipient->setNewsletterSubscription($isSubscribed);
    $recipient->setShop($this->getSiteName());
    $recipient->setSource(Drupal::request()->getHost());
    $this->setRecipientTags($user, $recipient);

    return $recipient;
  }

  /**
   * Gets site name from configuration.
   *
   * @return string
   *   Site name.
   */
  private function getSiteName() {
    if (empty($this->siteName)) {
      $this->siteName = $this->getConfigService()->getSiteName();
    }

    return $this->siteName;
  }

  /**
   * Gets CleverReach configuration service.
   *
   * @return \Drupal\cleverreach\Component\Infrastructure\ConfigService
   *   Configuration service instance.
   */
  private function getConfigService() {
    if (NULL === $this->configService) {
      $this->configService = ServiceRegister::getService(
        Configuration::CLASS_NAME
      );
    }

    return $this->configService;
  }

  /**
   * Gets User repository.
   *
   * @return \Drupal\cleverreach\Component\Repository\UserRepository
   */
  private function getUserRepository() {
    if (NULL === $this->userRepository) {
      $this->userRepository = new UserRepository();
    }

    return $this->userRepository;
  }

  /**
   * Checks if provided string is and email.
   *
   * @param string $input
   *   Text input that needs to be checked.
   *
   * @return bool
   *   If field is an email returns true, otherwise false.
   */
  private function isEmail($input) {
    return strpos($input, '@') !== FALSE;
  }

  /**
   * Checks if user is subscribed to newsletter.
   *
   * If newsletter field is not set on user entity, fallback value defined
   * before initial sync is used.
   *
   * @param \Drupal\user\Entity\User $user
   *   User object.
   *
   * @return bool
   *   If user is subscribed, returns true, otherwise false.
   */
  private function isSubscribed(User $user) {
    $isSubscribed = $user->get('field_cleverreach_subscribed')->getString();

    if ($isSubscribed === '') {
      $isSubscribed = $this->getConfigService()
        ->getDefaultRecipientStatus();
    }
    else {
      $isSubscribed = $isSubscribed === '1';
    }

    return $user->isActive() && $isSubscribed;
  }

  /**
   * Gets all user prefixed roles / groups.
   *
   * @return array
   *   Array of role names.
   */
  private function getUserRoles() {
    if (empty($this->userRoles)) {
      /** @var \Drupal\user\Entity\Role $role */
      foreach (user_role_names(TRUE) as $role) {
        $this->userRoles[] = $role;
      }
    }

    return $this->userRoles;
  }

  /**
   * Gets all taxonomy tags defined for user entity.
   *
   * @return array
   *   List of all taxonomies defined on user.
   */
  private function getTaxonomies() {
    return cleverreach_get_taxonomy_user_values();
  }

  /**
   * Gets array of website tags.
   *
   * @return array
   *   Array of site names.
   */
  private function getWebsites() {
    if (empty($this->websites)) {
      $this->websites = [$this->getConfigService()->getSiteName()];
    }

    return $this->websites;
  }

  /**
   * Sets tags on recipient.
   *
   * @param \CleverReach\BusinessLogic\Entity\Recipient $recipient
   *   Recipient object.
   * @param \Drupal\user\Entity\User $user
   *   User object.
   */
  private function setRecipientTags(User $user, Recipient $recipient) {
    $tags = $this->getTagsFormatted(
        $this->getUserRepository()->getRoleNamesByUser($user),
        self::TAG_TYPE_ROLE
    );

    $tags->add(
        $this->getTagsFormatted(
            $this->getWebsites(),
            self::TAG_TYPE_SITE
        )
    );

    $tags->add(
        $this->getTagsFormatted(
            $this->getUserTaxonomies($user),
            self::TAG_TYPE_TAXONOMY
        )
    );

    $recipient->setTags($tags);
  }

  /**
   * Gets all taxonomies defined on user.
   *
   * @param \Drupal\user\Entity\User $user
   *   User object.
   *
   * @return array
   *   List of all taxonomies defined on user.
   */
  private function getUserTaxonomies(User $user) {
    $taxonomies = [];
    $fields = cleverreach_get_taxonomy_user_fields();

    foreach ($fields as $fieldName => $field) {
      foreach ($user->get($fieldName)->getValue() as $value) {
        if (!$term = Term::load($value['target_id'])) {
          continue;
        }

        $taxonomies[] = $term->getName();
      }
    }

    return array_unique($taxonomies);
  }

}
