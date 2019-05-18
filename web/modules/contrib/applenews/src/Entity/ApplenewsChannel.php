<?php

namespace Drupal\applenews\Entity;

use Drupal\applenews\ChannelInterface;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines the Apple News Channel entity.
 *
 * @ContentEntityType(
 *   id = "applenews_channel",
 *   label = @Translation("Applenews channel"),
 *   label_collection = @Translation("Applenews channels"),
 *   label_singular = @Translation("Applenews channel"),
 *   label_plural = @Translation("Applenews channels"),
 *   label_count = @PluralTranslation(
 *     singular = "@count Applenews channel",
 *     plural = "@count Applenews channels",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\applenews\ChannelListBuilder",
 *     "route_provider" = {
 *       "html" = "\Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "form" = {
 *       "default" = "Drupal\applenews\Form\ChannelForm",
 *       "add" = "Drupal\applenews\Form\ChannelForm",
 *       "delete" = "Drupal\applenews\Form\ChannelDeleteForm"
 *     }
 *   },
 *   base_table = "applenews_channel",
 *   admin_permission = "administer applenews channels",
 *   entity_keys = {
 *     "id" = "cid",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode"
 *   },
 *   links = {
 *     "collection" = "/admin/config/services/applenews/channel",
 *     "add-form" = "/admin/config/services/applenews/channel/add",
 *     "edit-form" = "/admin/config/services/applenews/channel/{applenews_channel}",
 *     "refresh-form" = "/admin/config/services/applenews/channel/{applenews_channel}/refresh",
 *     "delete-form" = "/admin/config/services/applenews/channel/{applenews_channel}/delete",
 *   }
 * )
 */
class ApplenewsChannel extends ContentEntityBase implements ChannelInterface {

  /**
   * {@inheritdoc}
   */
  public function getCreatedAt() {
    // Sample data: 2018-07-27T20:15:08Z.
    return $this->get('createdAt')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getModifiedAt() {
    // Sample data: 2018-07-27T20:15:34Z.
    return $this->get('modifiedAt')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getChannelId() {
    return $this->get('id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->get('type')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getShareUrl() {
    return $this->get('shareUrl')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getLinks() {
    return $this->get('links')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getSections() {
    return $this->get('sections')->value ? unserialize($this->get('sections')->value) : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getWebsite() {
    return $this->get('website')->value;
  }

  /**
   * Updates channel details from Apple News.
   *
   * @see \Drupal\applenews\Commands\ApplenewsCommands::updateChannel()
   */
  public function updateMetaData() {
    $channel_id = $this->getChannelId();
    $publisher = \Drupal::service('applenews.publisher');

    // Fetch Channel details:
    $response = $publisher->getChannel($channel_id);
    $this->updateFromResponse($response);

    // Fetch sections.
    $response = $publisher->GetSections($channel_id);
    if ($response) {
      $this->updateSections($response);
    }

    return $this->save();
  }

  /**
   * Updates properties from reponse.
   *
   * @param object $response
   *   Response object.
   *
   * @return $this
   *   Current object.
   */
  public function updateFromResponse($response) {
    if (is_object($response) && isset($response->data)) {
      $channel = $response->data;
      $this->createdAt = $channel->createdAt;
      $this->modifiedAt = $channel->modifiedAt;
      $this->id = $channel->id;
      $this->type = $channel->type;
      $this->shareUrl = $channel->shareUrl;
      $this->links = serialize([
        'defaultSection' => $channel->links->defaultSection,
        'self' => $channel->links->self,
      ]);
      $this->name = $channel->name;
      $this->website = $channel->website;
    }
    return $this;
  }

  /**
   * Updates section details.
   *
   * @param object $response
   *   Response object.
   *
   * @return $this
   *   Current object.
   */
  public function updateSections($response) {
    $sections = [];
    foreach ($response->data as $section) {
      $sections[$section->id] = $section->name;
      if ($section->isDefault) {
        $sections[$section->id] .= ' (Default)';
      }
    }
    if ($sections) {
      $this->sections = serialize($sections);
    }

    return $this;
  }

  /**
   * Loads by channel ID.
   *
   * @param string $channel_id
   *   String channel ID.
   *
   * @return \Drupal\applenews\Entity\ApplenewsChannel|null
   *   Apple News Channel entity if exist. NULL otherwise.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public static function loadByChannelId($channel_id) {
    $query = \Drupal::entityQuery('applenews_channel');
    $ids = $query->condition('id', $channel_id)->execute();
    if ($ids) {
      $entity_type_manager = \Drupal::entityTypeManager();
      $channels = $entity_type_manager->getStorage('applenews_channel')->loadMultiple($ids);
      return array_shift($channels);
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    /** @var \Drupal\Core\Field\BaseFieldDefinition[] $fields */
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['uuid']->setDescription(new TranslatableMarkup('The channel UUID.'));

    $fields['id'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Channel ID'))
      ->setRequired(TRUE)
      ->addConstraint('UniqueField')
      ->addPropertyConstraints('value', ['Regex' => ['pattern' => '/^[a-z0-9\-]+$/']])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Name'))
      ->setDescription(new TranslatableMarkup('Channel name.'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ]);

    $fields['createdAt'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup("The channel created"))
      ->setSetting('max_length', 25)
      ->setReadOnly(TRUE)
      ->setDescription(new TranslatableMarkup('The created time of the channel. e.g. 2018-07-27T20:15:34Z'));

    $fields['modifiedAt'] = BaseFieldDefinition::create('string')
      ->setReadOnly(TRUE)
      ->setLabel(new TranslatableMarkup("The channel modified"))
      ->setSetting('max_length', 25)
      ->setDescription(new TranslatableMarkup('The modified time of the channel. e.g. 2018-07-27T20:15:34Z'));

    $fields['type'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup("The channel type"))
      ->setReadOnly(TRUE)
      ->setSetting('max_length', 10)
      ->setDescription(new TranslatableMarkup('The type of the channel.'));

    $fields['shareUrl'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup("The channel share URL"))
      ->setReadOnly(TRUE)
      ->setDescription(new TranslatableMarkup('The share URL of the channel. e.g. https://apple.news/DedSkwdsQrdSWbNitx0w'));

    $fields['links'] = BaseFieldDefinition::create('string_long')
      ->setReadOnly(TRUE)
      ->setLabel(new TranslatableMarkup("The channel links"))
      ->setDescription(new TranslatableMarkup('An array of links. Allowed index are self, defaultSection'));

    $fields['sections'] = BaseFieldDefinition::create('string_long')
      ->setReadOnly(TRUE)
      ->setLabel(new TranslatableMarkup("The channel sections"))
      ->setDescription(new TranslatableMarkup('An array of section details'));

    $fields['website'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup("Website"))
      ->setReadOnly(TRUE)
      ->setDescription(new TranslatableMarkup('The share URL of the channel. e.g. https://apple.news/DedSkwdsQrdSWbNitx0w'));

    return $fields;
  }

}
