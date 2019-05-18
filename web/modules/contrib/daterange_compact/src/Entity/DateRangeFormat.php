<?php

namespace Drupal\daterange_compact\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the date range format entity.
 *
 * @ConfigEntityType(
 *   id = "date_range_format",
 *   label = @Translation("Date range format"),
 *   handlers = {
 *     "list_builder" = "Drupal\daterange_compact\DateRangeFormatListBuilder",
 *     "form" = {
 *       "add" = "Drupal\daterange_compact\Form\DateRangeFormatForm",
 *       "edit" = "Drupal\daterange_compact\Form\DateRangeFormatForm",
 *       "delete" = "Drupal\daterange_compact\Form\DateRangeFormatDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "date_range_format",
 *   admin_permission = "administer site configuration",
 *   list_cache_tags = { "rendered" },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/regional/date_range_format/{date_range_format}",
 *     "add-form" = "/admin/config/regional/date_range_format/add",
 *     "edit-form" = "/admin/config/regional/date_range_format/{date_range_format}/edit",
 *     "delete-form" = "/admin/config/regional/date_range_format/{date_range_format}/delete",
 *     "collection" = "/admin/config/regional/date_range_format"
 *   }
 * )
 */
class DateRangeFormat extends ConfigEntityBase implements DateRangeFormatInterface {

  /**
   * The Date range format ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Date range format label.
   *
   * @var string
   */
  protected $label;

  /**
   * {@inheritdoc}
   */
  public function getDateSettings() {
    return $this->get('date_settings');
  }

  /**
   * {@inheritdoc}
   */
  public function getDateTimeSettings() {
    return $this->get('datetime_settings');
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTagsToInvalidate() {
    return ['rendered'];
  }

}
