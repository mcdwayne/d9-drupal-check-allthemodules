<?php
/**
 * Created by PhpStorm.
 * User: milan
 * Date: 1/31/19
 * Time: 10:45 AM
 */

namespace Drupal\hidden_tab\Plugable;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for all plugins of this module.
 */
abstract class HiddenTabPluginBase extends PluginBase implements HiddenTabPluginInterfaceBase {

  /**
   * See \Drupal\hidden_tab\Plugable\HiddenTabPluginBaseInterface::id().
   *
   * @var string
   *
   * @see \Drupal\hidden_tab\Plugable\HiddenTabPluginBaseInterface::id().
   */
  protected $PID;

  /**
   * See \Drupal\hidden_tab\Plugable\HiddenTabPageInterface::label().
   *
   * @var string
   *
   * @see \Drupal\hidden_tab\Plugable\HiddenTabPageInterface::label().
   */
  protected $HTPLabel;

  /**
   * See \Drupal\hidden_tab\Plugable\HiddenTabPluginBaseInterface.
   *
   * @var string
   *
   * @see \Drupal\hidden_tab\Plugable\HiddenTabPageInterface::description().
   */
  protected $HTPDescription;

  /**
   * See \Drupal\hidden_tab\Plugable\HiddenTabPluginBaseInterface::weight().
   *
   * @var string
   *
   * @see \Drupal\hidden_tab\Plugable\HiddenTabPluginBaseInterface::weight().
   */
  protected $HTPWeight;

  /**
   * See display().
   *
   * @var bool
   *
   * @see \Drupal\hidden_tab\Plugable\HiddenTabPluginBaseInterface::display().
   */
  protected $HTPDisplay;

  /**
   * See tags().
   *
   * @var array
   *
   * @see \Drupal\hidden_tab\Plugable\HiddenTabPluginBaseInterface::tags().
   */
  protected $HTPTags;

  /**
   * {@inheritdoc}
   */
  public function id(): string {
    return $this->PID;
  }

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return $this->HTPLabel;
  }

  /**
   * {@inheritdoc}
   */
  public function description(): string {
    return $this->HTPDescription;
  }

  /**
   * {@inheritdoc}
   */
  public function weight(): int {
    return $this->HTPWeight;
  }

  /**
   * {@inheritdoc}
   */
  public function display(): bool {
    return $this->HTPDisplay;
  }

  /**
   * {@inheritdoc}
   */
  public function tags(): array {
    return isset($this->HTPTags) ? $this->HTPTags : [];
  }

  protected function formElementBase(): string {
    return $this->id();
  }

  /**
   * {@inheritdoc}
   */
  public function handleConfigForm(array &$form, ?FormStateInterface $form_state, string $fs, $config) {

  }

  /**
   * {@inheritdoc}
   */
  public function handleConfigFormValidate(array &$form, FormStateInterface $form_state, $config) {

  }

  /**
   * {@inheritdoc}
   */
  public function handleConfigFormSubmit(?array &$form, FormStateInterface $form_state) {
    return $form_state->hasValue($this->formElementBase())
      ? $form_state->getValue($this->formElementBase())
      : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container,
                                array $configuration,
                                $plugin_id,
                                $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_id);
  }

}
