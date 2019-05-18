<?php
declare(strict_types=1);

namespace Drupal\membership_entity\MemberId;

use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a MemberId Plugin.
 *
 * @see \Drupal\membership_entity\MemberId\MemberIdManager
 */
interface MemberIdInterface {

  /**
   * Get the next available Member ID.
   *
   * @return string
   *   The next available Member ID.
   */
  public function next() : string;

  /**
   * Get a sample Member ID value/placeholder.
   *
   * This is useful for generating placeholder content during site building
   * or profiling.
   *
   * @return string
   *   A sample Member ID.
   */
  public function sampleValue() : string;

  /**
   * Define the options form.
   *
   * @param array $form
   *  An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   */
  public function optionsForm(array $form, FormStateInterface $form_state) : array;

}
