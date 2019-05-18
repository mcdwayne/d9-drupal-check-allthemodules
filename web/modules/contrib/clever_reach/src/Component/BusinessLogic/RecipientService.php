<?php

namespace Drupal\clever_reach\Component\BusinessLogic;

use CleverReach\BusinessLogic\Entity\Recipient;
use CleverReach\BusinessLogic\Entity\SpecialTag;
use CleverReach\BusinessLogic\Entity\SpecialTagCollection;
use CleverReach\BusinessLogic\Entity\Tag;
use CleverReach\BusinessLogic\Entity\TagCollection;
use CleverReach\BusinessLogic\Interfaces\Recipients;
use CleverReach\Infrastructure\Interfaces\Required\Configuration;
use CleverReach\Infrastructure\ServiceRegister;
use Drupal;
use Drupal\clever_reach\Component\Repository\UserRepository;
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
   * @var \Drupal\clever_reach\Component\Repository\UserRepository
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
   * Gets all tags as a collection.
   *
   * @return \CleverReach\BusinessLogic\Entity\TagCollection
   *   Collection of integration tags.
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
   * Gets all recipients for passed batch IDs with tags.
   *
   * SPECIAL ATTENTION should be pointed towards tags. They should be set
   * as TagCollection on Recipient instance.
   *
   * @param array $batchRecipientIds
   *   Array of recipient IDs that should be fetched.
   * @param bool $includeOrders
   *   If includeOrders flag is set to true, orders should
   *   also be returned with other recipient data, otherwise not.
   *
   * @return \CleverReach\BusinessLogic\Entity\Recipient[]
   *   Objects based on passed IDs.
   *
   * @see \CleverReach\BusinessLogic\Entity\Recipient
   *
   * @throws \CleverReach\Infrastructure\TaskExecution\Exceptions\RecipientsGetException
   *   When recipients can't be fetched.
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

      if ($user === NULL) {
        continue;
      }

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

    return $result;
  }

  /**
   * Gets all special tags as a collection.
   *
   * @return \CleverReach\BusinessLogic\Entity\SpecialTagCollection
   *   Collection of integration supported special tags.
   */
  public function getAllSpecialTags() {
    return new SpecialTagCollection(
          [
            SpecialTag::subscriber(),
            SpecialTag::contact(),
          ]
      );
  }

  /**
   * {@inheritdoc}
   */
  public function getAllRecipientsIds() {
    return $this->getUserRepository()->getAllIds();
  }

  /**
   * Informs service about completed synchronization of provided recipients IDs.
   *
   * @param array $recipientIds
   *   Array of recipient IDs that are successfully synchronized.
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
   * Creates recipient object from user object.
   *
   * @param \Drupal\user\Entity\User $user
   *   User entity.
   * @param string|null $email
   *   Email of user.
   *
   * @return \CleverReach\BusinessLogic\Entity\Recipient
   *   Recipient object.
   *
   * @throws \Exception
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
    $this->setRecipientSpecialTags($recipient);

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
   * @return \Drupal\clever_reach\Component\Infrastructure\ConfigService
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
   * @return \Drupal\clever_reach\Component\Repository\UserRepository
   *   CleverReach user repository.
   */
  private function getUserRepository() {
    if (NULL === $this->userRepository) {
      $this->userRepository = new UserRepository();
    }

    return $this->userRepository;
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

    return $isSubscribed;
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
    return clever_reach_get_taxonomy_user_values();
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
   * @param \Drupal\user\Entity\User $user
   *   User object.
   * @param \CleverReach\BusinessLogic\Entity\Recipient $recipient
   *   Recipient object.
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
    $fields = clever_reach_get_taxonomy_user_fields();

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

  /**
   * Sets special tag for recipient.
   *
   * @param \CleverReach\BusinessLogic\Entity\Recipient $recipient
   *   Recipient object.
   */
  private function setRecipientSpecialTags(Recipient $recipient) {
    $specialTags = new SpecialTagCollection([SpecialTag::contact()]);
    if ($recipient->getNewsletterSubscription()) {
      $specialTags->addTag(SpecialTag::subscriber());
    }
    $recipient->setSpecialTags($specialTags);
  }

}
