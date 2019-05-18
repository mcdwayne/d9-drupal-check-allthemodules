<?php

namespace Drupal\measuremail\Entity;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\Core\Routing\RequestHelper;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\measuremail\MeasuremailElementsInterface;
use Drupal\measuremail\MeasuremailElementsPluginCollection;
use Drupal\measuremail\MeasuremailInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Drupal\Core\Entity\Entity\EntityViewDisplay;

/**
 * Defines an measuremail configuration entity.
 *
 * @ConfigEntityType(
 *   id = "measuremail",
 *   label = @Translation("Measuremail"),
 *   handlers = {
 *     "form" = {
 *       "subscribe" = "Drupal\measuremail\Form\MeasuremailSubscribeForm",
 *       "add" = "Drupal\measuremail\Form\MeasuremailAddForm",
 *       "edit" = "Drupal\measuremail\Form\MeasuremailEditForm",
 *       "delete" = "Drupal\measuremail\Form\MeasuremailDeleteForm",
*        "settings" = "Drupal\measuremail\Form\MeasuremailSettingsForm",
 *     },
 *     "list_builder" = "Drupal\measuremail\MeasuremailListBuilder",
 *     "storage" = "Drupal\Core\Config\Entity\ConfigEntityStorage",
 *   },
 *   admin_permission = "administer measuremail",
 *   entity_keys = {
 *     "id" = "name",
 *     "label" = "label",
 *   },
 *   links = {
 *     "edit-form" = "/admin/structure/measuremail/manage/{measuremail}",
 *     "delete-form" = "/admin/structure/measuremail/manage/{measuremail}/delete",
 *     "collection" = "/admin/structure/measuremai",
 *     "settings" = "/admin/structure/measuremail/manage/{measuremail}/settings",
 *   },
 *   config_export = {
 *     "name",
 *     "label",
 *     "subscription",
 *     "elements",
 *     "settings",
 *   }
 * )
 */
class Measuremail extends ConfigEntityBase implements MeasuremailInterface, EntityWithPluginCollectionInterface {

  /**
   * The name of the measuremail.
   *
   * @var string
   */
  protected $name;

  /**
   * The measuremail label.
   *
   * @var string
   */
  protected $label;

  /**
   * The measuremail subscription id.
   *
   * @var string
   */
  protected $subscription;

  /**
   * The array of elements for this measuremail form.
   *
   * @var array
   */
  protected $elements = [];

  /**
   * Holds the collection of elements that are used by this measuremail form.
   *
   * @var \Drupal\measuremail\MeasuremailElementsPluginCollection
   */
  protected $elementsCollection;

  /**
   * The measuremail settings.
   *
   * @var array
   */
  protected $settings = [];

  /**
   * The measuremail settings original.
   *
   * @var string
   */
  protected $settingsOriginal;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function getElements() {
    if (!$this->elementsCollection) {
      $this->elementsCollection = new MeasuremailElementsPluginCollection($this->getMeasuremailElementsPluginManager(), $this->elements);
      $this->elementsCollection->sort();
    }
    return $this->elementsCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return ['elements' => $this->getElements()];
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);
  }

  /**
   * {@inheritdoc}
   */
  public function getElement($element) {
    return $this->getElements()->get($element);
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->get('label');
  }

  /**
   * {@inheritdoc}
   */
  public function setLabel($label) {
    $this->set('label', $label);
    return $this;
  }

  /**
   * Returns the measuremail elements plugin manager.
   *
   * @return \Drupal\Component\Plugin\PluginManagerInterface
   *   The measuremail elements plugin manager.
   */
  protected function getMeasuremailElementsPluginManager() {
    return \Drupal::service('plugin.manager.measuremail.elements');
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings() {
    // Settings should not be empty even.
    return (isset($this->settings)) ? $this->settings +
      self::getDefaultSettings() : self::getDefaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function setSettings(array $settings) {
    // Always apply the default settings.
    $this->settings += static::getDefaultSettings();

    // Now apply new settings.
    foreach ($settings as $name => $value) {
      if (array_key_exists($name, $this->settings)) {
        $this->settings[$name] = $value;
      }
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSetting($key, $default = FALSE) {
    $settings = $this->getSettings();
    $value = (isset($settings[$key])) ? $settings[$key] : NULL;
    if ($default) {
      return $value ?: \Drupal::config('measuremail.settings')->get('settings.default_' . $key);
    }
    else {
      return $value;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setSetting($key, $value) {
    $settings = $this->getSettings();
    $settings[$key] = $value;
    $this->setSettings($settings);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function resetSettings() {
    $this->settings = $this->settingsOriginal;
  }

  /**
   * {@inheritdoc}
   */
  public static function getDefaultSettings() {
    return [
      'id' => '',
      'endpoint' => '',
      'email_field' => '',
      'languages_enabled' => [],
      'submit_button' => 'Subscribe',
      'formversion' => '',
      'privacyurl' => '',
      'privacyversion' => '',
      'callback_type' => 'inlinemessage',
      'callback_url' => '',
      'message_success' => 'Successfully enrolled.',
      'message_update' => 'This e-mail address is already in use. Check your inbox to update your subscriptions or to unsubscribe.',
      'message_error' => 'An error has occurred.',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function addMeasuremailElement(array $configuration) {
    $configuration['uuid'] = $this->uuidGenerator()->generate();
    $this->getElements()->addInstanceId($configuration['uuid'], $configuration);
    return $configuration['uuid'];
  }

  /**
   * {@inheritdoc}
   */
  public function deleteMeasuremailElement(MeasuremailElementsInterface $element) {
    $this->getElements()->removeInstanceId($element->getUuid());
    $this->save();
    return $this;
  }

}
