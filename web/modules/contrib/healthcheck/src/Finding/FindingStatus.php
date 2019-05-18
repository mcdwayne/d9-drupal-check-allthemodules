<?php


namespace Drupal\healthcheck\Finding;

/**
 * Provides status constants for the Finding class.
 *
 * @see \Drupal\healthcheck\Finding\Finding;
 */
final class FindingStatus {

  /**
   * Used for Findings that require an immediate fix.
   */
  const CRITICAL = 20;

  /**
   * Used when you should fix the associated finding as soon as possible.
   */
  const ACTION_REQUESTED = 15;

  /**
   * Used when you need to review the finding to be sure it's correct.
   */
  const NEEDS_REVIEW = 10;

  /**
   * Used when the finding is considered good, safe, or best practice.
   */
  const NO_ACTION_REQUIRED = 5;

  /**
   * Used when the check could not be performed.
   */
  const NOT_PERFORMED = 0;

  /**
   * Get all the statuses as a descending priority sorted array.
   *
   * @return array
   *   An array of statuses.
   */
  public static function getAsArray() {
    return [
      static::CRITICAL,
      static::ACTION_REQUESTED,
      static::NEEDS_REVIEW,
      static::NO_ACTION_REQUIRED,
      static::NOT_PERFORMED,
    ];
  }

  /**
   * Get all the statuses as text constants keyed by numeric status.
   *
   * This method provides a canonical text version of the status, useful for
   * theme variables and other places so you can avoid a large switch statement.
   *
   * @return array
   *   An array of text constants keyed by status.
   */
  public static function getTextConstants() {
    return [
      static::CRITICAL            => 'finding_critical',
      static::ACTION_REQUESTED    => 'finding_action_requested',
      static::NEEDS_REVIEW        => 'finding_needs_review',
      static::NO_ACTION_REQUIRED  => 'finding_no_action_required',
      static::NOT_PERFORMED       => 'finding_not_performed',
    ];
  }

  /**
   * Get all the statuses keyed by text constant.
   *
   * @return array
   *   An array of statuses keyed by text constant.
   */
  public static function getAsArrayByConstants() {
    return [
      'finding_critical'           => static::CRITICAL,
      'finding_action_requested'   => static::ACTION_REQUESTED,
      'finding_needs_review'       => static::NEEDS_REVIEW,
      'finding_no_action_required' => static::NO_ACTION_REQUIRED,
      'finding_not_performed'      => static::NOT_PERFORMED,
    ];
  }

  /**
   * Get the statuses and their translated labels, suitable for display.
   *
   * @return array
   *   An array of display labels keyed by status.
   */
  public static function getLabels() {
    return [
      static::CRITICAL            => t('Critical'),
      static::ACTION_REQUESTED    => t('Action Requested'),
      static::NEEDS_REVIEW        => t('Needs Review'),
      static::NO_ACTION_REQUIRED  => t('No Action Required'),
      static::NOT_PERFORMED       => t('Not Performed'),
    ];
  }

  /**
   * Get the text constants for statuses and their translated labels.
   *
   * This method associates the text constants with the translated labels,
   * useful for form elements where a numeric value wouldn't work.
   *
   * @return array
   *   An array of display labels keyed by text constant.
   */
  public static function getLabelsByConstants() {
    return [
      'finding_critical'            => t('Critical'),
      'finding_action_requested'    => t('Action Requested'),
      'finding_needs_review'        => t('Needs Review'),
      'finding_no_action_required'  => t('No Action Required'),
      'finding_not_performed'       => t('Not Performed'),
    ];
  }

  /**
   * Gets the numeric status given the text constant.
   *
   * @param string $status_text
   *   A string containing a status text constant.
   *
   * @return bool|int
   *   The numeric status if found, FALSE otherwise.
   *
   * @see \Drupal\healthcheck\Finding\FindingStatus::getTextConstants()
   */
  public static function constantToNumeric($status_text) {
    $statuses = static::getAsArrayByConstants();

    return isset($statuses[$status_text]) ? $statuses[$status_text] : FALSE;
  }

  /**
   * Gets the status as a text constant given the numeric value.
   *
   * @param int $status
   *   The numeric status value.
   *
   * @return bool|string
   *   The status as a text constant if found, FALSE otherwise.
   *
   * @see \Drupal\healthcheck\Finding\FindingStatus::getAsArrayByConstants()
   */
  public static function numericToConstant($status) {
    $statuses = static::getTextConstants();

    return isset($statuses[$status]) ? $statuses[$status] : FALSE;
  }
}
