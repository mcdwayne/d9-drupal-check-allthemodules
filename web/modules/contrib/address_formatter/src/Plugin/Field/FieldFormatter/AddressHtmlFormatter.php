<?php

namespace Drupal\address_formatter\Plugin\Field\FieldFormatter;

use Drupal\address\AddressInterface;
use Drupal\address\FieldHelper;
use Drupal\address\Plugin\Field\FieldFormatter\AddressDefaultFormatter;
use Drupal\address_formatter\Entity\AddressFormatter;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;

/**
 * Plugin implementation of the 'Address html' formatter.
 *
 * @FieldFormatter(
 *   id = "address_html",
 *   label = @Translation("Address html"),
 *   field_types = {
 *     "address",
 *   }
 * )
 */
class AddressHtmlFormatter extends AddressDefaultFormatter implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'options' => '',
    ];
  }

  /**
   * Builds settings form.
   *
   * @param \Drupal\Core\Field\FormatterBase $formatter
   *   The formatter having this trait.
   *
   * @return array
   *   The render array for Options settings.
   */
  protected function buildSettingsForm(FormatterBase $formatter) {

    // Get list of option sets as an associative array.
    $options = address_formatter_options_list();

    $element['options'] = [
      '#title' => $formatter->t('Options'),
      '#type' => 'select',
      '#default_value' => $formatter->getSetting('options'),
      '#options' => $options,
    ];

    $element['links'] = [
      '#theme' => 'links',
      '#links' => [
        [
          'title' => $formatter->t('Create new option set'),
          'url' => Url::fromRoute('entity.address_formatter.add_form', [], [
            'query' => \Drupal::destination()->getAsArray(),
          ]),
        ],
        [
          'title' => $formatter->t('Manage options'),
          'url' => Url::fromRoute('entity.address_formatter.collection', [], [
            'query' => \Drupal::destination()->getAsArray(),
          ]),
        ],
      ],
      '#access' => \Drupal::currentUser()->hasPermission('administer address formatter'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = $this->buildSettingsSummary($this);

    return $summary;

  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    // Add the options setting.
    $element = $this->buildSettingsForm($this);

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $settings = $this->getSettings();
    $profileId = $settings['options'] ?? 'default';
    $addressFormatter = AddressFormatter::load($profileId);
    $options = $addressFormatter->getOptions();

    $elements = [];
    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#cache' => [
          'contexts' => [
            'languages:' . LanguageInterface::TYPE_INTERFACE,
          ],
        ],
      ];

      $langKey = $item->getCountryCode() . '-' . $langcode;
      $allKey = $item->getCountryCode() . '-all';
      if (isset($options[$langKey]) && $options[$langKey]['template']['value']) {
        $elements[$delta] += $this->viewHtmlElement($item, $options[$langKey]['template']['value'], $profileId, $langcode);
      }
      elseif (isset($options[$allKey]) && $options[$allKey]['template']['value']) {
        $elements[$delta] += $this->viewHtmlElement($item, $options[$allKey]['template']['value'], $profileId, 'all');
      }
      else {
        $elements[$delta]['#post_render'] = ['\Drupal\address\Plugin\Field\FieldFormatter\AddressDefaultFormatter::postRender'];
        $elements[$delta] += parent::viewElement($item, $langcode);
      }
    }

    return $elements;
  }

  /**
   * Builds a renderable array for a single address item.
   *
   * @param \Drupal\address\AddressInterface $address
   *   The address.
   * @param string $template
   *   The html template.
   * @param string $profileId
   *   The profile id.
   * @param string $key
   *   Can be language code or `all`.
   *
   * @return array
   *   A renderable array.
   */
  protected function viewHtmlElement(AddressInterface $address, $template, $profileId, $key) {
    $country_code = $address->getCountryCode();
    $countries = $this->countryRepository->getList();
    $country = Html::escape($countries[$country_code]);
    $address_format = $this->addressFormatRepository->get($country_code);
    $values = $this->getValues($address, $address_format);

    $content = [
      '#theme' => 'address_html',
      '#data' => [],
    ];
    $result = $template;
    foreach ($address_format->getUsedFields() as $field) {
      $property = FieldHelper::getPropertyName($field);
      $value = Html::escape($values[$field]);
      $result = str_replace("%{$property}%", $value, $result);
      $content['#data'][$property] = $value;
    }

    $result = str_replace('%country_code%', $country_code, $result);
    $result = str_replace('%country%', $country, $result);

    $content['#data']['country_code'] = $country_code;
    $content['#data']['country'] = $country;
    $content['#data']['key'] = $key;
    $content['#data']['address_html_formatter_id'] = $profileId;
    $content['#data']['content'] = Xss::filterAdmin($result);

    return [
      '#markup' => render($content),
    ];
  }

  /**
   * Builds the address formatter settings summary.
   *
   * @param \Drupal\Core\Field\FormatterBase $formatter
   *   The formatter having this trait.
   *
   * @return array
   *   The settings summary build array.
   */
  protected function buildSettingsSummary(FormatterBase $formatter) {
    $summary = [];

    // Load the selected options.
    $options = $this->loadOptions($formatter->getSetting('options'));

    // Build the options summary.
    $os_summary = $options ? $options->label() : $formatter->t('Default settings');
    $summary[] = $formatter->t('Option: %os_summary', ['%os_summary' => $os_summary]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();
    return $dependencies + $this->getOptionsDependencies($this);
  }

  /**
   * Loads the selected option.
   *
   * @param string $id
   *   This option set id.
   *
   * @return \Drupal\address_formatter\Entity\AddressFormatter
   *   The option set selected in the formatter settings.
   */
  protected function loadOptions($id) {
    return AddressFormatter::load($id);
  }

  /**
   * Return the currently configured option set as a dependency array.
   *
   * @param \Drupal\Core\Field\FormatterBase $formatter
   *   The formatter having this trait.
   *
   * @return array
   *   An array of option set dependencies
   */
  protected function getOptionsDependencies(FormatterBase $formatter) {
    $dependencies = [];
    $option_id = $formatter->getSetting('options');
    if ($option_id && $options = $this->loadOptions($option_id)) {
      // Add the options as dependency.
      $dependencies[$options->getConfigDependencyKey()][] = $options->getConfigDependencyName();
    }
    return $dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function onDependencyRemoval(array $dependencies) {
    $changed = parent::onDependencyRemoval($dependencies);

    if ($this->optionsDependenciesDeleted($this, $dependencies)) {
      $changed = TRUE;
    }
    return $changed;
  }

  /**
   * If a dependency is going to be deleted, set the option set to default.
   *
   * @param \Drupal\Core\Field\FormatterBase $formatter
   *   The formatter.
   * @param array $dependencies_deleted
   *   An array of dependencies that will be deleted.
   *
   * @return bool
   *   Whether or not option set dependencies changed.
   */
  protected function optionsDependenciesDeleted(FormatterBase $formatter, array $dependencies_deleted) {
    $option_id = $formatter->getSetting('options');
    if ($option_id && $options = $this->loadOptions($option_id)) {
      if (!empty($dependencies_deleted[$options->getConfigDependencyKey()]) && in_array($options->getConfigDependencyName(), $dependencies_deleted[$options->getConfigDependencyKey()])) {
        $formatter->setSetting('options', 'default');
        return TRUE;
      }
    }

    return FALSE;
  }

}
