<?php
/**
 * @file
 * Contains \Drupal\plus\Plugin\Setting\SettingBase.
 */

namespace Drupal\plus\Plugin\Setting;

use Drupal\plus\Plugin\ThemePluginBase;
use Drupal\plus\Utility\Element;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Base class for a setting.
 *
 * @ingroup plugins_setting
 */
class SettingBase extends ThemePluginBase implements SettingInterface {

  /**
   * {@inheritdoc}
   */
  public function buildElement(Element $form, FormStateInterface $form_state) {
    // Construct the group elements.
    $group = $this->buildGroup($form, $form_state);
    $plugin_id = $this->getPluginId();

    // Return the element if already constructed.
    if (isset($group->$plugin_id)) {
      return $group->$plugin_id;
    }

    // Set properties from the plugin definition.
    foreach ($this->getElementProperties() as $name => $value) {
      $group->$plugin_id->setProperty($name, $value);
    }

    // Set default value from the stored form state value or theme setting.
    $default_value = $form_state->getValue($plugin_id, $this->theme->getSetting($plugin_id));
    $group->$plugin_id->setProperty('default_value', $default_value);

    // Append additional "see" link references to the description.
    $description = (string) $group->$plugin_id->getProperty('description') ?: '';
    $links = [];
    foreach ($this->pluginDefinition['see'] as $url => $title) {
      $link = Element::create([
        '#type' => 'link',
        '#url' => Url::fromUri($url),
        '#title' => $title,
        '#attributes' => [
          'target' => '_blank',
        ],
      ]);
      $links[] = (string) $link->renderPlain();
    }
    if (!empty($links)) {
      $description .= '<br>';
      $description .= t('See also:');
      $description .= ' ' . implode(', ', $links);
      $group->$plugin_id->setProperty('description', $description);
    }

    return $group->$plugin_id;
  }

  /**
   * {@inheritdoc}
   */
  public function buildGroup(Element $form, FormStateInterface $form_state) {
    $groups = $this->getGroups();
    $group = $form;
    $first = TRUE;
    foreach ($groups as $key => $title) {
      if (!isset($group->$key)) {
        if ($title) {
          $group->$key = ['#type' => 'details', '#title' => $title];
        }
        else {
          $group->$key = ['#type' => 'container'];
        }
        $group = Element::reference($group->$key->getArray());
        if ($first) {
          $group->setProperty('group', 'bootstrap');
        }
        else {
          $group->setProperty('open', FALSE);
        }
      }
      else {
        $group = Element::reference($group->$key->getArray());
      }
      $first = FALSE;
    }
    return $group;
  }

  /**
   * {@inheritdoc}
   */
  public function drupalSettings() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function formAlter(Element $form, FormStateInterface $form_state, $form_id = NULL) {
    $this->buildElement($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return ['rendered'];
  }

  /**
   * Retrieves all the form properties from the setting definition.
   *
   * @return array
   *   The form properties.
   */
  public function getElementProperties() {
    $properties = $this->getPluginDefinition();
    foreach ($properties as $name => $value) {
      if (in_array($name, ['class', 'defaultValue', 'definition', 'groups', 'id', 'provider', 'see'])) {
        unset($properties[$name]);
      }
    }
    return $properties;
  }


  /**
   * {@inheritdoc}
   */
  public function getDefaultValue() {
    return isset($this->pluginDefinition['defaultValue']) ? $this->pluginDefinition['defaultValue'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroups() {
    return !empty($this->pluginDefinition['groups']) ? $this->pluginDefinition['groups'] : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions() {
    return isset($this->pluginDefinition['options']) ? (array) $this->pluginDefinition['options'] : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return !empty($this->pluginDefinition['title']) ? $this->pluginDefinition['title'] : NULL;
  }

}
