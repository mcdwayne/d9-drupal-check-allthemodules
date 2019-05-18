<?php

/**
 * @file
 * Hooks for prepared_data module. All hooks can and should
 * be placed into <mymodulename>.prepared_data.inc.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * After prepared data has been build for the first time.
 *
 * @param \Drupal\prepared_data\PreparedDataInterface $data
 *   The prepared data as wrapped object.
 */
function hook_prepared_data_build(\Drupal\prepared_data\PreparedDataInterface $data) {}

/**
 * After prepared data has been refreshed.
 *
 * @param \Drupal\prepared_data\PreparedDataInterface $data
 *   The prepared data as wrapped object.
 */
function hook_prepared_data_refreshed(\Drupal\prepared_data\PreparedDataInterface $data) {}

/**
 * After the given processor initialized regards the given data.
 *
 * See also hook_prepared_data_initialized_by_PROCESSOR().
 *
 * @param \Drupal\prepared_data\Processor\ProcessorInterface $processor
 *   The processor instance.
 * @param \Drupal\prepared_data\PreparedDataInterface $data
 *   The data as wrapped object.
 */
function hook_prepared_data_initialized_by(\Drupal\prepared_data\Processor\ProcessorInterface $processor, \Drupal\prepared_data\PreparedDataInterface $data) {}

/**
 * After the processor with plugin ID PROCESSOR initialized regards the given data.
 *
 * @param \Drupal\prepared_data\Processor\ProcessorInterface $processor
 *   The processor instance.
 * @param \Drupal\prepared_data\PreparedDataInterface $data
 *   The corresponding data as wrapped object.
 */
function hook_prepared_data_initialized_by_PROCESSOR(\Drupal\prepared_data\Processor\ProcessorInterface $processor, \Drupal\prepared_data\PreparedDataInterface $data) {}

/**
 * After the given processor processed regards the given data.
 *
 * See also hook_prepared_data_processed_by_PROCESSOR().
 *
 * @param \Drupal\prepared_data\Processor\ProcessorInterface $processor
 *   The processor instance.
 * @param \Drupal\prepared_data\PreparedDataInterface $data
 *   The data as wrapped object.
 */
function hook_prepared_data_processed_by(\Drupal\prepared_data\Processor\ProcessorInterface $processor, \Drupal\prepared_data\PreparedDataInterface $data) {}

/**
 * After the processor with plugin ID PROCESSOR processed regards the given data.
 *
 * @param \Drupal\prepared_data\Processor\ProcessorInterface $processor
 *   The processor instance.
 * @param \Drupal\prepared_data\PreparedDataInterface $data
 *   The corresponding data as wrapped object.
 */
function hook_prepared_data_processed_by_PROCESSOR(\Drupal\prepared_data\Processor\ProcessorInterface $processor, \Drupal\prepared_data\PreparedDataInterface $data) {}

/**
 * After the given processor finished regards the given data.
 *
 * See also hook_prepared_data_finished_by_PROCESSOR().
 *
 * @param \Drupal\prepared_data\Processor\ProcessorInterface $processor
 *   The processor instance.
 * @param \Drupal\prepared_data\PreparedDataInterface $data
 *   The data as wrapped object.
 */
function hook_prepared_data_finished_by(\Drupal\prepared_data\Processor\ProcessorInterface $processor, \Drupal\prepared_data\PreparedDataInterface $data) {}

/**
 * After the processor with plugin ID PROCESSOR finished regards the given data.
 *
 * @param \Drupal\prepared_data\Processor\ProcessorInterface $processor
 *   The processor instance.
 * @param \Drupal\prepared_data\PreparedDataInterface $data
 *   The corresponding data as wrapped object.
 */
function hook_prepared_data_finished_by_PROCESSOR(\Drupal\prepared_data\Processor\ProcessorInterface $processor, \Drupal\prepared_data\PreparedDataInterface $data) {}

/**
 * After the given processor cleaned up regards the given data.
 *
 * See also hook_prepared_data_cleanup_by_PROCESSOR().
 *
 * @param \Drupal\prepared_data\Processor\ProcessorInterface $processor
 *   The processor instance.
 * @param \Drupal\prepared_data\PreparedDataInterface $data
 *   The data as wrapped object.
 */
function hook_prepared_data_cleanup_by(\Drupal\prepared_data\Processor\ProcessorInterface $processor, \Drupal\prepared_data\PreparedDataInterface $data) {}

/**
 * After the processor with plugin ID PROCESSOR cleaned up regards the given data.
 *
 * @param \Drupal\prepared_data\Processor\ProcessorInterface $processor
 *   The processor instance.
 * @param \Drupal\prepared_data\PreparedDataInterface $data
 *   The corresponding data as wrapped object.
 */
function hook_prepared_data_cleanup_by_PROCESSOR(\Drupal\prepared_data\Processor\ProcessorInterface $processor, \Drupal\prepared_data\PreparedDataInterface $data) {}

/**
 * @} End of "addtogroup hooks".
 */
