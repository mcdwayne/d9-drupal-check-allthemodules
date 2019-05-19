<?php

namespace Drupal\timelinejs\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Timeline entity.
 *
 * @ingroup timelinejs
 *
 * @ContentEntityType(
 *   id = "timeline",
 *   label = @Translation("Timeline"),
 *   label_plural = @Translation("Timelines"),
 *   handlers = {
 *     "storage" = "Drupal\timelinejs\TimelineStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\timelinejs\TimelineListBuilder",
 *     "views_data" = "Drupal\timelinejs\Entity\TimelineViewsData",
 *     "translation" = "Drupal\timelinejs\TimelineTranslationHandler",
 *
 *     "form" = {
 *       "default" = "Drupal\timelinejs\Form\TimelineForm",
 *       "add" = "Drupal\timelinejs\Form\TimelineForm",
 *       "edit" = "Drupal\timelinejs\Form\TimelineForm",
 *       "delete" = "Drupal\timelinejs\Form\TimelineDeleteForm",
 *     },
 *     "access" = "Drupal\timelinejs\TimelineAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\timelinejs\TimelineHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "timeline",
 *   data_table = "timeline_field_data",
 *   revision_table = "timeline_revision",
 *   revision_data_table = "timeline_field_revision",
 *   translatable = TRUE,
 *   admin_permission = "administer timeline entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/timeline/{timeline}",
 *     "add-form" = "/admin/content/timeline/add",
 *     "edit-form" = "/admin/content/timeline/{timeline}/edit",
 *     "delete-form" = "/admin/content/timeline/{timeline}/delete",
 *     "version-history" = "/admin/content/timeline/{timeline}/revisions",
 *     "revision" = "/admin/content/timeline/{timeline}/revisions/{timeline_revision}/view",
 *     "revision_revert" = "/admin/content/timeline/{timeline}/revisions/{timeline_revision}/revert",
 *     "translation_revert" = "/admin/content/timeline/{timeline}/revisions/{timeline_revision}/revert/{langcode}",
 *     "revision_delete" = "/admin/content/timeline/{timeline}/revisions/{timeline_revision}/delete",
 *     "collection" = "/admin/content/timeline",
 *   },
 *   field_ui_base_route = "timeline.settings"
 * )
 */
class Timeline extends RevisionableContentEntityBase implements TimelineInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    foreach (array_keys($this->getTranslationLanguages()) as $langcode) {
      $translation = $this->getTranslation($langcode);

      // If no owner has been set explicitly, make the anonymous user the owner.
      if (!$translation->getOwner()) {
        $translation->setOwnerId(0);
      }
    }

    // If no revision author has been set explicitly, make the timeline
    // owner the revision author.
    if (!$this->getRevisionUser()) {
      $this->setRevisionUserId($this->getOwnerId());
    }
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
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isPublished() {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published) {
    $this->set('status', $published ? TRUE : FALSE);
    return $this;
  }

  /**
   * Sets the Timeline scale.
   *
   * @param string $scale
   *   Either 'human' or 'cosmological'.
   *
   * @return \Drupal\timelinejs\Entity\TimelineInterface
   *   The called Timeline entity.
   */
  public function setSale($scale) {
    $this->set('scale', $scale);
    return $this;
  }

  /**
   * Gets the Timeline scale.
   *
   * @return string
   *   The scale of the Timeline, either 'human' or 'cosmological'.
   */
  public function getScale() {
    return $this->get('scale')->value;
  }

  /**
   * Gets the Google Spreadsheet Url.
   *
   * @return string
   *   The public url of the Google Spreadsheet.
   */
  public function getGoogleSpreadsheetUrl() {
    /** @var \Drupal\Core\Field\FieldItemListInterface $urlFieldList */
    $urlFieldList = $this->get('google_spreadsheet_url');

    if ($urlFieldList->isEmpty()) {
      return '';
    }

    /** @var \Drupal\Core\Field\Plugin\Field\FieldType\UriItem $url */
    $url = $urlFieldList->get(0);
    return $url->value;
  }

  /**
   * Gets the Google Spreadsheet Url.
   *
   * @param string $url
   *   The url of the Google Spreadsheet.
   *
   * @return \Drupal\timelinejs\Entity\TimelineInterface
   *   The called Timeline entity.
   */
  public function setGoogleSpreadsheetUrl($url) {
    $this->set('google_spreadsheet_url', $url);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setHashBookmark($hashBookmark) {
    $this->set('hash_bookmark', $hashBookmark);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getHashBookmark() {
    return (bool) $this->get('hash_bookmark')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setStartAtEnd($startAtEnd) {
    $this->set('start_at_end', $startAtEnd);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStartAtEnd() {
    return (bool) $this->get('start_at_end')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setUseBc($useBc) {
    $this->set('use_bc', $useBc);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getUseBc() {
    return (bool) $this->get('use_bc')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setDragging($dragging) {
    $this->set('dragging', $dragging);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDragging() {
    return (bool) $this->get('dragging')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTrackResize($trackResize) {
    $this->set('track_resize', $trackResize);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTrackResize() {
    return (bool) $this->get('track_resize')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setDefaultBackgroundColor($color) {
    $this->set('default_bg_color', $color);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultBackgroundColor() {
    return (string) $this->get('default_bg_color')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setScaleFactor($scaleFactor) {
    $this->set('scale_factor', $scaleFactor);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getScaleFactor() {
    return (float) $this->get('scale_factor')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setInitialZoom($initialZoom) {
    $this->set('initial_zoom', $initialZoom);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getInitialZoom() {
    return (int) $this->get('initial_zoom')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setZoomSequence(array $zoomSequence) {
    $this->set('zoom_sequence', $zoomSequence);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getZoomSequence() {
    $zoomSequenceValues = [];

    /** @var \Drupal\Core\Field\FieldItemListInterface $zoomSequence */
    $zoomSequence = $this->get('zoom_sequence');
    foreach ($zoomSequence as $zoomLevel) {
      $zoomSequenceValues[] = (float) $zoomLevel->value;
    }

    return $zoomSequenceValues;
  }

  /**
   * {@inheritdoc}
   */
  public function setNavigationPosition($position) {
    $this->set('timenav_position', $position);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getNavigationPosition() {
    return (string) $this->get('timenav_position')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setOptimalTickWidth($width) {
    $this->set('optimal_tick_width', $width);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOptimalTickWidth() {
    return (int) $this->get('optimal_tick_width')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setBaseClass($class) {
    $this->set('base_class', $class);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseClass() {
    return (string) $this->get('base_class')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setNavigationHeight($height) {
    $this->set('timenav_height', $height);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getNavigationHeight() {
    return (int) $this->get('timenav_height')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setNavigationHeightPercentage($height) {
    $this->set('timenav_height_percentage', $height);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getNavigationHeightPercentage() {
    return (float) $this->get('timenav_height_percentage')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setNavigationMobileHeightPercentage($height) {
    $this->set('timenav_mobile_height_percentage', $height);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getNavigationMobileHeightPercentage() {
    return (float) $this->get('timenav_mobile_height_percentage')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setNavigationHeightMin($height) {
    $this->set('timenav_height_min', $height);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getNavigationHeightMin() {
    return (int) $this->get('timenav_height_min')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setMarkerHeightMin($height) {
    $this->set('marker_height_min', $height);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMarkerHeightMin() {
    return (int) $this->get('marker_height_min')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setMarkerWidthMin($width) {
    $this->set('marker_width_min', $width);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMarkerWidthMin() {
    return (int) $this->get('marker_width_min')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setMarkerPadding($padding) {
    $this->set('marker_padding', $padding);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMarkerPadding() {
    return (int) $this->get('marker_padding')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setStartSlide($startSlide) {
    $this->set('start_at_slide', $startSlide);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStartSlide() {
    return (int) $this->get('start_at_slide')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setMenubarHeight($height) {
    $this->set('menubar_height', $height);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMenubarHeight() {
    return (int) $this->get('menubar_height')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setAnimationDuration($duration) {
    $this->set('duration', $duration);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAnimationDuration() {
    return (int) $this->get('duration')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setEase($ease) {
    $this->set('ease', $ease);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getEase() {
    return $this->get('ease')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setSlidePaddingLeftRight($padding) {
    $this->set('slide_padding_lr', $padding);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSlidePaddingLeftRight() {
    return (int) $this->get('slide_padding_lr')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setSlideDefaultFade($fade) {
    $this->set('slide_default_fade', $fade);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSlideDefaultFade() {
    return (string) $this->get('slide_default_fade')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setGoogleAnalyticsPropertyId($googleAnalyticsPropertyId) {
    $this->set('ga_property_id', $googleAnalyticsPropertyId);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getGoogleAnalyticsPropertyId() {
    return !empty($this->get('ga_property_id')->value) ? (int) $this->get('ga_property_id')->value : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Define Drupal entity metadata.
    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Timeline entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Timeline entity.'))
      ->setRevisionable(TRUE)
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -10,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Timeline is published.'))
      ->setRevisionable(TRUE)
      ->setDefaultValue(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['revision_translation_affected'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Revision translation affected'))
      ->setDescription(t('Indicates if the last edit of a translation belongs to current revision.'))
      ->setReadOnly(TRUE)
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    // Define Timeline data source.
    $fields['google_spreadsheet_url'] = BaseFieldDefinition::create('uri')
      ->setLabel(t('Google Spreadsheet URL'))
      ->setDescription(t('Copy and paste the spreadsheet URL. The spreadsheet must be published and follows the TimelineJS format. See https://timeline.knightlab.com/docs/using-spreadsheets.html for more information.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'uri',
        'weight' => -6,
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE);

    // Define options used for displaying Timeline JS.
    $fields['scale'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Scale'))
      ->setDescription(t("Either human or cosmological. The cosmological scale is required to handle dates in the very distant past or future. (Before Tuesday, April 20th, 271,821 BCE after Saturday, September 13 275,760 CE) For the cosmological scale, only the year is considered, but it's OK to have a cosmological timeline with years between 271,821 BCE and 275,760 CE."))
      ->setRequired(TRUE)
      ->setTranslatable(FALSE)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -5,
      ])
      ->setSetting('allowed_values', [
        'human' => t('Human'),
        'cosmological' => t('Cosmological'),
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDefaultValue('human')
      ->setRevisionable(TRUE);

    $fields['hash_bookmark'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Hash Bookmark'))
      ->setDescription(t('If set TimelineJS will update the browser url whenever the event changes. This allows people to link to specific slides.'))
      ->setDisplayOptions('form', [
        'type' => 'checkbox',
        'weight' => 0,
      ])
      ->setDefaultValue(FALSE)
      ->setRevisionable(TRUE);

    $fields['start_at_end'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Start at End'))
      ->setDescription(t('If set TimelineJS will start at the last event.'))
      ->setDisplayOptions('form', [
        'type' => 'checkbox',
        'weight' => 0,
      ])
      ->setDefaultValue(FALSE)
      ->setRevisionable(TRUE);

    $fields['use_bc'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Use BC'))
      ->setDescription(t('Use Dates earlier than 0 by using the suffix BC.'))
      ->setDisplayOptions('form', [
        'type' => 'checkbox',
        'weight' => 0,
      ])
      ->setDefaultValue(FALSE)
      ->setRevisionable(TRUE);

    $fields['dragging'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Dragging'))
      ->setDescription(t('Turn Dragging on or off.'))
      ->setDisplayOptions('form', [
        'type' => 'checkbox',
        'weight' => 0,
      ])
      ->setDefaultValue(TRUE)
      ->setRevisionable(TRUE);

    $fields['track_resize'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Track Resize'))
      ->setDescription(t('Turn track resize on or off.'))
      ->setDisplayOptions('form', [
        'type' => 'checkbox',
        'weight' => 0,
      ])
      ->setDefaultValue(TRUE)
      ->setRevisionable(TRUE);

    $fields['default_bg_color'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Default Background Color'))
      ->setDescription(t('Default background colors for each slide.'))
      ->setDisplayOptions('form', [
        'type' => 'color',
        'weight' => 0,
      ])
      ->setDefaultValue('#ffffff')
      ->setRevisionable(TRUE);

    $fields['scale_factor'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Scale Factor'))
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 0,
      ])
      ->setDescription(t('How many screen widths wide the timeline should be at first presentation.'))
      ->setDefaultValue(2)
      ->setRevisionable(TRUE);

    $fields['initial_zoom'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Initial Zoom'))
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 0,
      ])
      ->setDescription(t('The position in the zoom_sequence series used to scale the Timeline when it is first created. Takes precedence over scale_factor.'))
      ->setRevisionable(TRUE);

    $fields['zoom_sequence'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Zoom Sequence'))
      ->setDescription(t('Array of values for TimeNav zoom levels. Each value is a scale_factor, which means that at any given level, the full timeline would require that many screens to display all events.'))
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 0,
      ])
      ->setDefaultValue([0.5, 1, 2, 3, 5, 8, 13, 21, 34, 55, 89])
      ->setRevisionable(TRUE);

    $fields['timenav_position'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Timeline Navigation Position'))
      ->setDescription(t('Display the timeline navigation on the top or bottom.'))
      ->setRequired(TRUE)
      ->setTranslatable(FALSE)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 0,
      ])
      ->setSetting('allowed_values', [
        'top' => t('Top'),
        'bottom' => t('Bottom'),
      ])
      ->setDefaultValue('bottom')
      ->setRevisionable(TRUE);

    $fields['optimal_tick_width'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Optimal Tick Width'))
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 0,
      ])
      ->setDescription(t('Optimal distance (in pixels) between ticks on axis..'))
      ->setDefaultValue(100)
      ->setRevisionable(TRUE);

    $fields['base_class'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Base class'))
      ->setDescription(t('Removing the <em>tl-timeline</em> base class will disable all default stylesheets.'))
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
      ->setDefaultValue('tl-timeline')
      ->setRevisionable(TRUE);

    $fields['timenav_height'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Timeline Navigation Height'))
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 0,
      ])
      ->setDescription(t('The height in pixels of the timeline nav. Takes precedence over timenav_height_percentage.'))
      ->setDefaultValue(150)
      ->setRevisionable(TRUE);

    $fields['timenav_height_percentage'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Timeline Navigation Height Percentage'))
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 0,
      ])
      ->setDescription(t('Specify the timeline navigation height as a percentage of the screen instead of in pixels.'))
      ->setDefaultValue(25)
      ->setRevisionable(TRUE);

    $fields['timenav_mobile_height_percentage'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Timeline Navigation Mobile Height Percentage'))
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 0,
      ])
      ->setDescription(t('Specify the timeline nav height as a percentage of a mobile device screen.'))
      ->setDefaultValue(40)
      ->setRevisionable(TRUE);

    $fields['timenav_height_min'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Timeline Navigation Height Minimum'))
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 0,
      ])
      ->setDescription(t('The minimum timeline nav height (in pixels).'))
      ->setDefaultValue(150)
      ->setRevisionable(TRUE);

    $fields['marker_height_min'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Marker Height Minimum'))
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 0,
      ])
      ->setDescription(t('The minimum marker height (in pixels).'))
      ->setDefaultValue(30)
      ->setRevisionable(TRUE);

    $fields['marker_width_min'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Marker Width Minimum'))
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 0,
      ])
      ->setDescription(t('The minimum marker width (in pixels).'))
      ->setDefaultValue(100)
      ->setRevisionable(TRUE);

    $fields['marker_padding'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Marker padding'))
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 0,
      ])
      ->setDescription(t('Top and bottom padding (in pixels) for markers.'))
      ->setDefaultValue(5)
      ->setRevisionable(TRUE);

    $fields['start_at_slide'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Start at slide'))
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 0,
      ])
      ->setDescription(t('The first slide to display when the timeline is loaded.'))
      ->setDefaultValue(0)
      ->setRevisionable(TRUE);

    $fields['menubar_height'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Menubar height'))
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 0,
      ])
      ->setDescription(t('Menubar height.'))
      ->setDefaultValue(0)
      ->setRevisionable(TRUE);

    $fields['duration'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Duration'))
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 0,
      ])
      ->setDescription(t('Animation duration (in milliseconds).'))
      ->setDefaultValue(1000)
      ->setRevisionable(TRUE);

    $fields['ease'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Easing'))
      ->setDescription(t('Easing.'))
      ->setRequired(TRUE)
      ->setTranslatable(FALSE)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 0,
      ])
      ->setSetting('allowed_values', Timeline::getEasingOptionsArray())
      ->setDefaultValue('bottom')
      ->setRevisionable(TRUE);

    $fields['slide_padding_lr'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Slide padding'))
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 0,
      ])
      ->setDescription(t('Padding (in pixels) on the left and right of each slide.'))
      ->setDefaultValue(100)
      ->setRevisionable(TRUE);

    $fields['slide_default_fade'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Slide Default Fade'))
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
      ->setDescription(t('Padding (in pixels) on the left and right of each slide.'))
      ->setDefaultValue("0%")
      ->setRevisionable(TRUE);

    // TODO: Determine if language option should be configurable.
    // TODO: Determine how we can pull this form the google analytics
    // module if installed.
    $fields['ga_property_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Google Analytics ID'))
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
      ->setDescription(t('Google Analytics ID'))
      ->setDefaultValue('')
      ->setRevisionable(TRUE);

    // TODO: Determine how trackable events should be managed.
    return $fields;
  }

  /**
   * Provides an options array for easing.
   *
   * @return array
   *   An array of easing options.
   */
  public static function getEasingOptionsArray() {
    return [
      'easeInSpline' => 'easeInSpline',
      'easeInOutExpo' => 'easeInOutExpo',
      'easeOut' => 'easeOut',
      'easeOutStrong' => 'easeOutStrong',
      'easeIn' => 'easeIn',
      'easeInStrong' => 'easeInStrong',
      'easeOutBounce' => 'easeOutBounce',
      'easeInBack' => 'easeInBack',
      'easeOutBack' => 'easeOutBack',
      'bounce' => 'bounce',
      'bouncePast' => 'bouncePast',
      'swingTo' => 'swingTo',
      'swingFrom' => 'swingFrom',
      'elastic' => 'elastic',
      'spring' => 'spring',
      'blink' => 'blink',
      'pulse' => 'pulse',
      'wobble' => 'wobble',
      'sinusoidal' => 'sinusoidal',
      'flicker' => 'flicker',
      'mirror' => 'mirror',
      'easeInQuad' => 'easeInQuad',
      'easeOutQuad' => 'easeOutQuad',
      'easeInOutQuad' => 'easeInOutQuad',
      'easeInCubic' => 'easeInCubic',
      'easeOutCubic' => 'easeOutCubic',
      'easeInOutCubic' => 'easeInOutCubic',
      'easeInQuart' => 'easeInQuart',
      'easeOutQuart' => 'easeOutQuart',
      'easeInOutQuart' => 'easeInOutQuart',
      'easeInQuint' => 'easeInQuint',
      'easeOutQuint' => 'easeOutQuint',
      'easeInOutQuint' => 'easeInOutQuint',
    ];
  }

}
