<?php

namespace Drupal\carto_sync;

/**
 * Interface CartoSyncApiInterface.
 *
 * @package Drupal\carto_sync
 */
interface CartoSyncApiInterface {

  /**
   * Checks whether the service is available or not.
   *
   * @return bool
   *   TRUE if the service is available, otherwise FALSE.
   */
  public function available();

  /**
   * Checks whether a dataset name exists in CARTO or not.
   *
   * @param string $dataset
   *   The dataset name.
   *
   * @return bool
   *   TRUE if the dataset name exists, otherwise FALSE.
   *
   * @throws CartoSyncException
   */
  public function datasetExists($dataset);

  /**
   * Retrieves the number of rows in a given CARTO dataset name.
   *
   * @param string $dataset
   *   The dataset name.
   *
   * @return int
   *   Integer indicating the number of rows in the given dataset.
   */
  public function getDatasetRows($dataset);

  /**
   * Generates the CARTO admin dataset URL.
   *
   * @param string $dataset
   *   The dataset name.
   *
   * @return \Drupal\Core\Url
   *   URL object to the given dataset.
   */
  public function getDatasetUrl($dataset);

  /**
   * Import the Drupal data in a CARTO dataset.
   *
   * @param string $path
   *   The file to import path
   */
  public function importDataset($path);

  /**
   * Reomves a CARTO dataset given its name.
   *
   * @param string $dataset
   *   The dataset name.
   *
   * @return bool
   *   Bool indicating whether the it as successfully removed or not.
   */
  public function deleteDataset($dataset);

}
