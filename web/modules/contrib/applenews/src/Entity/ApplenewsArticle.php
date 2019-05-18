<?php

namespace Drupal\applenews\Entity;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Defines the Apple News article entity.
 *
 * @ContentEntityType(
 *   id = "applenews_article",
 *   label = @Translation("Applenews article"),
 *   label_collection = @Translation("Applenews article"),
 *   label_singular = @Translation("Applenews article"),
 *   label_plural = @Translation("Applenews article"),
 *   label_count = @PluralTranslation(
 *     singular = "@count Applenews article",
 *     plural = "@count Applenews articles",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\applenews\ChannelListBuilder",
 *     "route_provider" = {
 *       "html" = "\Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "form" = {
 *       "default" = "Drupal\applenews\Form\ChannelForm",
 *     }
 *   },
 *   base_table = "applenews_article",
 *   admin_permission = "administer applenews article",
 *   entity_keys = {
 *     "id" = "article_id",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode"
 *   },
 *   links = {
 *     "collection" = "/admin/config/services/applenews/article",
 *   }
 * )
 */
class ApplenewsArticle extends ContentEntityBase {

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
  public function getArticleId() {
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
  public function getRevision() {
    return $this->get('revision')->value;
  }

  /**
   * Update properties from object.
   *
   * @param object $response
   *   Response object.
   *
   * @return $this
   *   Current object.
   */
  public function updateFromResponse($response) {
    if (is_object($response) && isset($response->data)) {
      $article = $response->data;
      $this->createdAt = $article->createdAt;
      $this->modifiedAt = $article->modifiedAt;
      $this->id = $article->id;
      $this->type = $article->type;
      $this->shareUrl = $article->shareUrl;
      $this->links = serialize([
        'channel' => $article->links->channel,
        'sections' => $article->links->sections,
        'self' => $article->links->self,
      ]);
      $this->revision = $article->revision;
    }
    return $this;
  }

  /**
   * Returns create date in system formats.
   *
   * @param string $type
   *   String type.
   * @param string|null $format
   *   String format.
   *
   * @return string
   *   String formatted created date.
   */
  public function getCreatedFormatted($type = 'medium', $format = NULL) {
    return $this->formatDate($this->get('createdAt')->value, $type, $format);
  }

  /**
   * Returns modified date in system formats.
   *
   * @param string $type
   *   String type.
   * @param string|null $format
   *   String format.
   *
   * @return string
   *   String formatted date
   */
  public function getModifiedFormatted($type = 'medium', $format = NULL) {
    return $this->formatDate($this->get('modifiedAt')->value, $type, $format);
  }

  /**
   * Formats given datetime string.
   *
   * @param string $date
   *   String date to format.
   * @param string $type
   *   String type.
   * @param string|null $format
   *   String format.
   *
   * @return string|null
   *   String formatted date
   */
  protected function formatDate($date, $type = 'medium', $format = NULL) {
    if (!$date) {
      return NULL;
    }
    /** @var \Drupal\Core\Datetime\DateFormatter $date_formatter */
    $date_formatter = \Drupal::service('date.formatter');
    $created = DrupalDateTime::createFromFormat('Y-m-d\TH:i:s\Z', $date);
    return $date_formatter->format($created->getTimestamp(), $type, $format);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    /** @var \Drupal\Core\Field\BaseFieldDefinition[] $fields */
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['uuid']->setDescription(new TranslatableMarkup('The article UUID.'));

    $fields['entity_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Entity ID'))
      ->setDescription(t('The ID of the entity of which this article attached.'))
      ->setRequired(TRUE);

    $fields['entity_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Entity type'))
      ->setDescription(t('The entity type to which this article is attached.'))
      ->setSetting('is_ascii', TRUE)
      ->setSetting('max_length', EntityTypeInterface::ID_MAX_LENGTH);

    $fields['field_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Apple News field name'))
      ->setDescription(t('The field name through which this article was added.'))
      ->setSetting('is_ascii', TRUE)
      ->setSetting('max_length', FieldStorageConfig::NAME_MAX_LENGTH);

    $fields['id'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Article ID'))
      ->setRequired(TRUE)
      ->addConstraint('UniqueField')
      ->addPropertyConstraints('value', ['Regex' => ['pattern' => '/^[a-z0-9\-]+$/']]);

    $fields['revision'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup("The article revision"))
      ->setReadOnly(TRUE)
      ->setDescription(new TranslatableMarkup('The revision of the article.'));

    $fields['createdAt'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup("The article created"))
      ->setSetting('max_length', 25)
      ->setReadOnly(TRUE)
      ->setDescription(new TranslatableMarkup('The created time of the article. e.g. 2018-07-27T20:15:34Z'));

    $fields['modifiedAt'] = BaseFieldDefinition::create('string')
      ->setReadOnly(TRUE)
      ->setLabel(new TranslatableMarkup("The article modified"))
      ->setSetting('max_length', 25)
      ->setDescription(new TranslatableMarkup('The modified time of the article. e.g. 2018-07-27T20:15:34Z'));

    $fields['type'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup("The article type"))
      ->setReadOnly(TRUE)
      ->setSetting('max_length', 10)
      ->setDescription(new TranslatableMarkup('The type of the article.'));

    $fields['shareUrl'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup("The article share URL"))
      ->setReadOnly(TRUE)
      ->setDescription(new TranslatableMarkup('The share URL of the article. e.g. https://apple.news/DedSkwdsQrdSWbNitx0w'));

    $fields['links'] = BaseFieldDefinition::create('string_long')
      ->setReadOnly(TRUE)
      ->setLabel(new TranslatableMarkup("The article links"))
      ->setDescription(new TranslatableMarkup('An array of links. Allowed index are self, defaultSection'));

    return $fields;
  }

}
