<?php
/**
 * Created by PhpStorm.
 * User: milan
 * Date: 1/31/19
 * Time: 10:46 AM
 */

namespace Drupal\hidden_tab\Plugable;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

interface HiddenTabPluginInterfaceBase extends ContainerFactoryPluginInterface {

  /**
   * Id of the plugin.
   *
   * @return string
   *   Id of the plugin.
   */
  public function id(): string;

  /**
   * Label of the plugin.
   *
   * @return string
   *   Label of the plugin.
   */
  public function label(): string;

  /**
   * Admin description of the plugin.
   *
   * @return string
   *   Admin description of the plugin.
   */
  public function description(): string;

  /**
   * Weight of the plugin among other plugins when sorting them.
   *
   * @return int
   *   Weight of the plugin among other plugins when sorting them.
   */
  public function weight(): int;

  /**
   * A set of tags for various stuff.
   *
   * @return array
   *   A set of tags for various stuff.
   */
  public function tags(): array;

  /**
   * Gives the plugin a chance to add additional configuration.
   *
   * @param array $form
   *   The form of configuration.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of configuration.
   * @param string $form_fieldset
   *   All the elements added by plugin must go into this fieldset in form.
   * @param mixed|null $config
   *   The previous configuration stored by the plugin (json_decoded).
   *
   * @return null
   *   Nothing.
   */
  public function handleConfigForm(array &$form, ?FormStateInterface $form_state, string $form_fieldset, $config);

  /**
   * Validate configurations added by the plugin.
   *
   * @param array $form
   *   The form being validated.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state being validated.
   * @param mixed|null $config
   *   The previous configuration stored by the plugin (json_decoded).
   *
   * @return null
   *   Nothing.
   */
  public function handleConfigFormValidate(array &$form, FormStateInterface $form_state,$config);

  /**
   * Submit configurations added by the plugin.
   *
   * @param array|null $form
   *   The form of configuration.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of configuration.
   *
   * @return mixed
   *   Any data (will be json_encoded later) the plugin wishes to store.
   */
  public function handleConfigFormSubmit(?array &$form, FormStateInterface $form_state);

}
