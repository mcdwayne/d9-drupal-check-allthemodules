<?php

namespace Drupal\better_links\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\link\Plugin\Field\FieldWidget\LinkWidget;

/**
 * Plugin implementation of the 'better_links_field_widget' widget.
 *
 * @FieldWidget(
 *   id = "better_links_field_widget",
 *   label = @Translation("Better Link"),
 *   field_types = {
 *     "link"
 *   }
 * )
 */
class BetterLinksFieldWidget extends LinkWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'better_links_class_mode' => 'manual',
      'better_links_class_force' => 'btn btn-primary',
      'better_links_class_select' => "btn btn-default|Default\nbtn btn-primary|Primary\nbtn btn-link|Link",
      'better_links_target_mode' => 'manual',
      'better_links_target_force' => '_self',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);
    $field_name = $this->fieldDefinition->getName();

    // Target
    $element['better_links_target_mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('Method for adding class'),
      '#options' => $this->getTargetModeOptions(),
      '#default_value' => $this->getSetting('better_links_target_mode'),
      '#description' => $this->t('Select the method you want to use for adding class.'),
    ];

    $element['better_links_target_force'] = [
      '#type' => 'select',
      '#title' => $this->t('Select a target'),
      '#options' => $this->getTargetSelectOptions(),
      '#default_value' => $this->getSetting('better_links_target_force'),
      '#description' => $this->t('Specifies where to open the linked document'),
      '#states' => [
        'visible' => [
          ':input[name="fields[' . $field_name . '][settings_edit_form][settings][better_links_target_mode]"]' => ['value' => 'force_target'],
        ],
      ],
    ];

    //  Classes
    $element['better_links_class_mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('Method for adding class'),
      '#options' => $this->getClassModeOptions(),
      '#default_value' => $this->getSetting('better_links_class_mode'),
      '#description' => $this->t('Select the method you want to use for adding class.'),
    ];

    $element['better_links_class_force'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link classes'),
      '#default_value' => $this->getSetting('better_links_class_force'),
      '#description' => $this->t('Set the classes to add on each link. Classes must be separated by a space.'),
      '#attributes' => [
        'placeholder' => 'btn btn-default',
      ],
      '#size' => '30',
      '#states' => [
        'visible' => [
          ':input[name="fields[' . $field_name . '][settings_edit_form][settings][better_links_class_mode]"]' => ['value' => 'force_class'],
        ],
      ],
    ];

    $element['better_links_class_select'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Define possibles classes'),
      '#default_value' => $this->getSetting('better_links_class_select'),
      '#description' => $this->selectClassDescription(),
      '#attributes' => [
        'placeholder' => 'btn btn-default|Default button' . PHP_EOL . 'btn btn-primary|Primary button',
      ],
      '#size' => '30',
      '#states' => [
        'visible' => [
          ':input[name="fields[' . $field_name . '][settings_edit_form][settings][better_links_class_mode]"]' => ['value' => 'select_class'],
        ],
      ],
    ];

    return $element;
  }

  /**
   * Return the description for the class select mode.
   */
  protected function selectClassDescription() {
    $description = '<p>' . t('The possible classes this link can have. Enter one value per line, in the format key|label.');
    $description .= '<br/>' . t('The key is the string which will be used as a class on a link. The label will be used on edit forms.');
    $description .= '<br/>' . t('If the key contains several classes, each class must be separated by a <strong>space</strong>.');
    $description .= '<br/>' . t('The label is optional: if a line contains a single string, it will be used as key and label.');
    $description .= '</p>';
    return $description;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $class_option = $this->getSetting('better_links_class_mode');
    $summary[] = $this->t('Class Mode: @better_links_class_mode', ['@better_links_class_mode' => $this->getClassModeOptions($class_option)]);
    if ($class_option == 'force_class') {
      $summary[] = $this->t('Class(es) added: @better_links_class_force', ['@better_links_class_force' => $this->getSetting('better_links_class_force')]);
    }
    if ($class_option == 'select_class') {
      $classes_available = $this->getClassSelectOptions($this->getSetting('better_links_class_select'), TRUE);
      $summary[] = $this->t('Class(es) available: @better_links_class_select', ['@better_links_class_select' => $classes_available]);
    }

    $target_option = $this->getSetting('better_links_target_mode');
    $summary[] = $this->t('Target Mode: @better_links_target_mode', ['@better_links_target_mode' => $this->getTargetModeOptions($target_option)]);
    if ($target_option == 'force_target') {
      $target_set = $this->getTargetSelectOptions($this->getSetting('better_links_target_force'), TRUE);
      $summary[] = $this->t('Target: @better_links_target_force', ['@better_links_target_force' => $target_set]);
    }


    return $summary;
  }

  /**
   * Return the options availables for the widget.
   *
   * @param string|null $key
   *   The optionnal key to retrieve.
   *
   * @return array|mixed
   *   The options array or the value corresponding to $key.
   */
  protected function getClassModeOptions($key = NULL) {
    $options = [
      'force_class' => $this->t('Class are automatically added'),
      'select_class' => $this->t('Let users select a class from a list'),
      'manual' => $this->t('Users can set a class manually'),
    ];

    if ($key && isset($options[$key])) {
      return $options[$key];
    }

    return $options;
  }

  /**
   * Return the options availables for the widget.
   *
   * @param string|null $key
   *   The optionnal key to retrieve.
   *
   * @return array|mixed
   *   The options array or the value corresponding to $key.
   */
  protected function getTargetModeOptions($key = NULL) {
    $options = [
      'force_target' => $this->t('Target is automatically added'),
      'manual' => $this->t('Users can set a target manually'),
    ];

    if ($key && isset($options[$key])) {
      return $options[$key];
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $item = $this->getLinkItem($items, $delta);
    $options = $item->get('options')->getValue();

    $target_mode = $this->getSetting('better_links_target_mode');
    switch ($target_mode) {
      case 'manual':
        $element['options']['attributes']['target'] = [
          '#type' => 'select',
          '#title' => $this->t('Select a target'),
          '#options' => $this->getTargetSelectOptions(),
          '#default_value' => !empty($options['attributes']['target']) ? $options['attributes']['target'] : '_self',
          '#description' => $this->t('Specifies where to open the linked document'),
        ];
        break;
      case 'force_target':
        $element['options']['attributes']['target'] = [
          '#type' => 'value',
          '#value' => $this->getSetting('better_links_target_force'),
        ];
        break;
    }

    $class_mode = $this->getSetting('better_links_class_mode');
    switch ($class_mode) {
      case 'manual':
        $element['options']['attributes']['class'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Link classes'),
          '#default_value' => !empty($options['attributes']['class']) ? $options['attributes']['class'] : '',
          '#description' => $this->t('Add classes to the link. The classes must be separated by a space.'),
          '#size' => '30',
        ];
        break;

      case 'select_class':
        /** @var \Drupal\link\LinkItemInterface $item */
        $classes_available = $this->getClassSelectOptions($this->getSetting('better_links_class_select'));
        $default_value = !empty($options['attributes']['class']) ? $options['attributes']['class'] : '';
        $element['options']['attributes']['class'] = [
          '#type' => 'select',
          '#title' => $this->t('Select a style'),
          '#options' => ['' => $this->t('- None -')] + $classes_available,
          '#default_value' => $default_value,
        ];
        break;

      case 'force_class':
        $element['options']['attributes']['class'] = [
          '#type' => 'value',
          '#value' => $this->getSetting('better_links_class_force'),
        ];
        break;

    }

    return $element;
  }

  /**
   * Getting link items.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   Returning of field items.
   * @param string $delta
   *   Returning field delta with item.
   *
   * @return \Drupal\link\LinkItemInterface
   *   Returning link items inteface.
   */
  private function getLinkItem(FieldItemListInterface $items, $delta) {
    /** @var \Drupal\link\LinkItemInterface $item */
    return $items[$delta];
  }

  /**
   * Convert textarea lines into an array.
   *
   * @param string $string
   *   The textarea lines to explode.
   * @param bool $summary
   *   A flag to return a formatted list of classes available.
   *
   * @return array
   *   An array keyed by the classes.
   */
  protected function getClassSelectOptions($string, $summary = FALSE) {
    $options = [];
    $lines = preg_split("/\\r\\n|\\r|\\n/", trim($string));
    $lines = array_filter($lines);

    foreach ($lines as $line) {
      list($class, $label) = explode('|', trim($line));
      $label = $label ?: $class;
      $options[$class] = $label;
    }

    if ($summary) {
      return implode(', ', array_keys($options));
    }

    return $options;

  }

  /**
   * Convert textarea lines into an array.
   *
   * @param string $string
   *   The textarea lines to explode.
   * @param bool $summary
   *   A flag to return a formatted list of classes available.
   *
   * @return array
   *   An array keyed by the classes.
   */
  protected function getTargetSelectOptions($key = '_self', $summary = FALSE) {
    $options = [
      '_self' => 'None',
      '_blank' => 'New Window',
      '_parent' => 'Parent Window',
      '_top' => 'Top Window',
    ];

    if ($summary) {
      return $options[$key];
    }

    return $options;
  }

}
