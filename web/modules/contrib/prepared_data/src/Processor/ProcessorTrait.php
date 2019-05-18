<?php

namespace Drupal\prepared_data\Processor;

use Drupal\prepared_data\PreparedDataInterface;

/**
 * A basic trait for data processors.
 */
trait ProcessorTrait {

  /**
   * Whether the processor is enabled or not.
   *
   * @var bool
   */
  protected $enabled;

  /**
   * Whether the processor is active or not.
   *
   * @var bool
   */
  protected $active;

  /**
   * Whether this processor is enabled or not.
   *
   * Not enabled processors would mean that they are constantly disabled,
   * and that there's no use for them at all. If you need temporary active
   * or inactive processors, have a look at ::isActive() and ::setActive().
   *
   * @return bool
   *   TRUE if the processor is enabled, FALSE otherwise.
   */
  public function isEnabled() {
    return !empty($this->enabled);
  }

  /**
   * Enables or disables the processor.
   *
   * @param bool $enabled
   *   Set to FALSE to disable the processor.
   */
  public function setEnabled($enabled = TRUE) {
    $this->enabled = $enabled;
  }

  /**
   * Whether the processor is active or not.
   *
   * Activity of processors can be limited by the user.
   * Every enabled processor has a certain activity defined.
   * To disable a processor permanently, have a look at
   * ::isEnabled() and ::setEnabled().
   *
   * @return bool
   *   TRUE if the processor is active, FALSE otherwise.
   */
  public function isActive() {
    return $this->isEnabled() && !empty($this->active);
  }

  /**
   * Set or unset the processor as active.
   *
   * @param bool $active
   *   Use FALSE to set the processor as inactive.
   */
  public function setActive($active = TRUE) {
    $this->active = $active;
  }

  /**
   * Perform necessary steps in order to be prepared for processing.
   *
   * This method will be invoked at first on all enabled processors.
   * Processor implementations may gather required information,
   * which then can be put into PreparedDataInterface::info().
   *
   * Whenever possible, this method should only add or manipulate
   * required information for later processing the prepared data.
   *
   * @param \Drupal\prepared_data\PreparedDataInterface $data
   *   The prepared data as wrapped object.
   *
   * @throws \Drupal\prepared_data\Processor\ProcessorRuntimeException
   *   In case something goes wrong at processor execution.
   */
  public function initialize(PreparedDataInterface $data) {
  }

  /**
   * Perform the main processing to generate prepared data.
   *
   * This method will be invoked right after
   * all processors have been initialized.
   * Generated data can be put into PreparedDataInterface::data().
   *
   * @param \Drupal\prepared_data\PreparedDataInterface $data
   *   The prepared data as wrapped object.
   *
   * @throws \Drupal\prepared_data\Processor\ProcessorRuntimeException
   *   In case something goes wrong at processor execution.
   */
  public function process(PreparedDataInterface $data) {
  }

  /**
   * Perform any required post processing steps.
   *
   * This method will be invoked right after
   * all processors have performed main processing.
   *
   * This method can be used for optimization and
   * calculating the validness of the generated data.
   *
   * @param \Drupal\prepared_data\PreparedDataInterface $data
   *   The prepared data as wrapped object.
   *
   * @throws \Drupal\prepared_data\Processor\ProcessorRuntimeException
   *   In case something goes wrong at processor execution.
   */
  public function finish(PreparedDataInterface $data) {
  }

  /**
   * Perform cleanup regards the given data.
   *
   * This method will be invoked right after all processors
   * finished their processing.
   *
   * This method will always be invoked, regardless whether the
   * user enabled the processor or not. A processor, which
   * used to build data, but is not in use anymore, may then
   * remove its previously added data inside this method, enclosed
   * by a check for ::isEnabled().
   *
   * @param \Drupal\prepared_data\PreparedDataInterface $data
   *   The prepared data as wrapped object.
   *
   * @throws \Drupal\prepared_data\Processor\ProcessorRuntimeException
   *   In case something goes wrong at processor execution.
   */
  public function cleanup(PreparedDataInterface $data) {
  }

}
