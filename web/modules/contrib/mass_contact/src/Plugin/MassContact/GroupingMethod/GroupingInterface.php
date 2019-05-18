<?php

namespace Drupal\mass_contact\Plugin\MassContact\GroupingMethod;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defineds a grouping method interface.
 */
interface GroupingInterface extends PluginInspectionInterface, DerivativeInspectionInterface, ConfigurablePluginInterface {

  /**
   * Retrieve the list of users by category.
   *
   * @param array $categories
   *   An array of category IDs for which to retrieve users. For instance,
   *   in the role grouping this would be an array of role IDs.
   *
   * @return int[]
   *   An array of recipient user IDs.
   */
  public function getRecipients(array $categories);

  /**
   * Display list of categories.
   *
   * @param array $categories
   *   An array of category IDs.
   *
   * @return string
   *   Display included categories as a string.
   */
  public function displayCategories(array $categories);

  /**
   * Builds the form for selecting categories for a mass contact.
   *
   * @param array $form
   *   The form definition array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public function adminForm(array &$form, FormStateInterface $form_state);

  /**
   * Retrieves a list of category IDs.
   *
   * @return array
   *   An array of category IDs (role IDs, term IDs, etc).
   */
  public function getCategories();

}
