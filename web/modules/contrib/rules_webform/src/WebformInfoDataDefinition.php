<?php

namespace Drupal\rules_webform;

use Drupal\Core\TypedData\MapDataDefinition;

/**
 * A typed data definition class for defining 'webform_info'.
 */
class WebformInfoDataDefinition extends MapDataDefinition {

  /**
   * Creates a new 'webform_info' definition.
   *
   * @param string $type
   *   (optional) The data type of the map Defaults to 'webform_info'.
   *
   * @return static
   */
  public static function create($type = 'webform_info') {
    $definition['type'] = $type;
    $webform_info_definition = new static($definition);

    $webform_info_definition->setPropertyDefinition('id', \Drupal::typedDataManager()
      ->createDataDefinition('string')
      ->setLabel('Webform ID'));

    $webform_info_definition->setPropertyDefinition('title', \Drupal::typedDataManager()
      ->createDataDefinition('string')
      ->setLabel('Webform Title'));

    $webform_info_definition->setPropertyDefinition('submitter_id', \Drupal::typedDataManager()
      ->createDataDefinition('string')
      ->setLabel('Submitter ID'));

    $webform_info_definition->setPropertyDefinition('submitter_name', \Drupal::typedDataManager()
      ->createDataDefinition('string')
      ->setLabel('Submitter Name'));

    $webform_info_definition->setPropertyDefinition('submitter_email', \Drupal::typedDataManager()
      ->createDataDefinition('string')
      ->setLabel('Submitter Email'));

    /* ------ Submission Created Date and Time in different formats ---------------------------- */

    $created_definition = \Drupal::typedDataManager()
      ->createDataDefinition('map')
      ->setLabel('Submission Creation Date and Time');

    $created_definition->setPropertyDefinition('timestamp', \Drupal::typedDataManager()
      ->createDataDefinition('string')
      ->setLabel('Timestamp'));

    $created_definition->setPropertyDefinition('date_short', \Drupal::typedDataManager()
      ->createDataDefinition('string')
      ->setLabel('Format: Date Short'));

    $created_definition->setPropertyDefinition('date_medium', \Drupal::typedDataManager()
      ->createDataDefinition('string')
      ->setLabel('Format: Date Medium'));

    $created_definition->setPropertyDefinition('date_long', \Drupal::typedDataManager()
      ->createDataDefinition('string')
      ->setLabel('Format: Date Long'));

    $created_definition->setPropertyDefinition('html_datetime', \Drupal::typedDataManager()
      ->createDataDefinition('string')
      ->setLabel('Format: html_datetime'));

    $created_definition->setPropertyDefinition('html_date', \Drupal::typedDataManager()
      ->createDataDefinition('string')
      ->setLabel('Format: html_date'));

    $created_definition->setPropertyDefinition('html_time', \Drupal::typedDataManager()
      ->createDataDefinition('string')
      ->setLabel('Format: html_time'));

    $webform_info_definition->setPropertyDefinition('created', $created_definition);

    /* ------ Submission Completed Date and Time in different formats ---------------------------- */

    $completed_definition = \Drupal::typedDataManager()
      ->createDataDefinition('map')
      ->setLabel('Submission Completed Date and Time');

    $completed_definition->setPropertyDefinition('timestamp', \Drupal::typedDataManager()
      ->createDataDefinition('string')
      ->setLabel('Timestamp'));

    $completed_definition->setPropertyDefinition('date_short', \Drupal::typedDataManager()
      ->createDataDefinition('string')
      ->setLabel('Format: Date Short'));

    $completed_definition->setPropertyDefinition('date_medium', \Drupal::typedDataManager()
      ->createDataDefinition('string')
      ->setLabel('Format: Date Medium'));

    $completed_definition->setPropertyDefinition('date_long', \Drupal::typedDataManager()
      ->createDataDefinition('string')
      ->setLabel('Format: Date Long'));

    $completed_definition->setPropertyDefinition('html_datetime', \Drupal::typedDataManager()
      ->createDataDefinition('string')
      ->setLabel('Format: html_datetime'));

    $completed_definition->setPropertyDefinition('html_date', \Drupal::typedDataManager()
      ->createDataDefinition('string')
      ->setLabel('Format: html_date'));

    $completed_definition->setPropertyDefinition('html_time', \Drupal::typedDataManager()
      ->createDataDefinition('string')
      ->setLabel('Format: html_time'));

    $webform_info_definition->setPropertyDefinition('completed', $completed_definition);

    /* ------ Submission Changed Date and Time in different formats ---------------------------- */

    $changed_definition = \Drupal::typedDataManager()
      ->createDataDefinition('map')
      ->setLabel('Submission Changed Date and Time');

    $changed_definition->setPropertyDefinition('timestamp', \Drupal::typedDataManager()
      ->createDataDefinition('string')
      ->setLabel('Timestamp'));

    $changed_definition->setPropertyDefinition('date_short', \Drupal::typedDataManager()
      ->createDataDefinition('string')
      ->setLabel('Format: Date Short'));

    $changed_definition->setPropertyDefinition('date_medium', \Drupal::typedDataManager()
      ->createDataDefinition('string')
      ->setLabel('Format: Date Medium'));

    $changed_definition->setPropertyDefinition('date_long', \Drupal::typedDataManager()
      ->createDataDefinition('string')
      ->setLabel('Format: Date Long'));

    $changed_definition->setPropertyDefinition('html_datetime', \Drupal::typedDataManager()
      ->createDataDefinition('string')
      ->setLabel('Format: html_datetime'));

    $changed_definition->setPropertyDefinition('html_date', \Drupal::typedDataManager()
      ->createDataDefinition('string')
      ->setLabel('Format: html_date'));

    $changed_definition->setPropertyDefinition('html_time', \Drupal::typedDataManager()
      ->createDataDefinition('string')
      ->setLabel('Format: html_time'));

    $webform_info_definition->setPropertyDefinition('changed', $changed_definition);

    /* ----------------------------------------------------------------------------------------- */

    $webform_info_definition->setPropertyDefinition('number', \Drupal::typedDataManager()
      ->createDataDefinition('string')
      ->setLabel('Submission Number'));

    $webform_info_definition->setPropertyDefinition('id_submission', \Drupal::typedDataManager()
      ->createDataDefinition('string')
      ->setLabel('Submission ID'));

    $webform_info_definition->setPropertyDefinition('uuid', \Drupal::typedDataManager()
      ->createDataDefinition('string')
      ->setLabel('Submission UUID'));

    $webform_info_definition->setPropertyDefinition('uri', \Drupal::typedDataManager()
      ->createDataDefinition('string')
      ->setLabel('Submission URI'));

    $webform_info_definition->setPropertyDefinition('ip', \Drupal::typedDataManager()
      ->createDataDefinition('string')
      ->setLabel('Submitter IP address'));

    $webform_info_definition->setPropertyDefinition('language', \Drupal::typedDataManager()
      ->createDataDefinition('string')
      ->setLabel('Submission language'));

    $webform_info_definition->setPropertyDefinition('is_draft', \Drupal::typedDataManager()
      ->createDataDefinition('string')
      ->setLabel('Submission Is draft'));

    $webform_info_definition->setPropertyDefinition('current_page', \Drupal::typedDataManager()
      ->createDataDefinition('string')
      ->setLabel('Submission Current page'));

    return $webform_info_definition;
  }

}
