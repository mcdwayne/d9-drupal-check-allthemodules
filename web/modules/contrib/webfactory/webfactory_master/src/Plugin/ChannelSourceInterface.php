<?php

namespace Drupal\webfactory_master\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webfactory_master\Entity\ChannelEntity;

/**
 * Specify channel source plugin behavior.
 *
 * @see \Drupal\webfactory_master\Annotation\ChannelSource
 * @see \Drupal\webfactory_master\Plugin\ChannelSourcePluginManager
 * @see \Drupal\webfactory_master\Plugin\Channel\ChannelSourceBase
 * @see plugin_api
 */
interface ChannelSourceInterface extends PluginInspectionInterface {

  /**
   * Initialize plugin with channel entity and specific settings.
   *
   * @param ChannelEntity $entity
   *   The channel entity.
   * @param array $settings
   *   An array of settings.
   */
  public function setConfiguration(ChannelEntity $entity, array $settings);

  /**
   * Return entities according to settings.
   *
   * @param int $limit
   *   Max number of element to get.
   * @param int $offset
   *   Offset of the element to get.
   *
   * @return array
   *   Entities.
   */
  public function entities($limit = NULL, $offset = NULL);

  /**
   * Return total number of entities of the channel current query.
   *
   * @return int
   *   Total number of entities.
   */
  public function getNbTotalEntities();

  /**
   * Defines plugin settings form.
   *
   * @param array $form
   *   The form element.
   * @param FormStateInterface $form_state
   *   The form state element.
   */
  public function getSettingsForm(array $form, FormStateInterface $form_state);

  /**
   * Retrieve plugin settings to store in channel entity.
   *
   * @param array $form
   *   The form element.
   * @param FormStateInterface $form_state
   *   The form state element.
   */
  public function getSettings(array $form, FormStateInterface $form_state);

}
