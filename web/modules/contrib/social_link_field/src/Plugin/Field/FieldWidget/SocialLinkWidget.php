<?php

namespace Drupal\social_link_field\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Plugin implementation of the 'open_hours' widget.
 *
 * @FieldWidget(
 *   id = "social_links",
 *   label = @Translation("Social links"),
 *   field_types = {
 *     "social_links"
 *   }
 * )
 */
class SocialLinkWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * Platform of social networks.
   *
   * @var array
   */
  protected $platforms;

  /**
   * Route name.
   *
   * @var string
   */
  protected $routeName;

  /**
   * Field cardinality.
   *
   * @var int
   */
  protected $cardinality;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, $platforms_service, $route_match) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->platforms = $platforms_service->getPlatforms();
    $this->routeName = $route_match->getRouteName();
    $this->cardinality = $this
      ->fieldDefinition
      ->getFieldStorageDefinition()
      ->getCardinality();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('plugin.manager.social_link_field.platform'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'select_social' => FALSE,
      'disable_weight' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = [];
    if ($this->cardinality > 0) {
      $element['select_social'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Possibility to select social network'),
        '#default_value' => $this->getSetting('select_social'),
      ];
    }
    $element['disable_weight'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Forbidden to change weight'),
      '#default_value' => $this->getSetting('disable_weight'),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getName();
    $parents = array_merge($element['#field_parents'], [$field_name, $delta]);
    $element['#parents'] = $parents;

    // Detect if we need to show or hide social select.
    // We show social select if this setting is checked,
    // cardinality is unlimited, or we are on field config form.
    $enable_social = (
      $this->getSetting('select_social')
      || $this->cardinality == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED
      || !(stripos($this->routeName, 'entity.field_config') === FALSE)
    ) ? TRUE : FALSE;

    // Detect default values.
    $default_vales = $this->getFormValues($enable_social, $items, $delta);
    $social = $default_vales['social'];
    $link = $default_vales['link'];

    // Social network select.
    $element['social'] = [
      '#type' => $enable_social ? 'select' : 'hidden',
      '#title' => $this->t('Social network'),
      '#default_value' => $social,
      '#attributes' => [
        'class' => ['social-select'],
      ],
      '#data' => [
        'field_name' => $field_name,
        'delta' => $delta,
      ],
      '#ajax' => [
        'event' => 'change',
        'callback' => [$this, 'updateLinkName'],
      ],
    ];
    foreach ($this->platforms as $platform) {
      $element['social']['#options'][$platform['id']]
        = $platform['name']->getUntranslatedString();
    }
    // Social link input.
    $element['link'] = [
      '#type' => 'textfield',
      '#title' => $enable_social ? $this->t('Profile link') : $this->platforms[$social]['name']->getUntranslatedString(),
      '#default_value' => $link,
      '#attributes' => [],
      '#required' => 0,
      '#field_prefix' => $this->platforms[$social]['urlPrefix'],
      '#prefix' => '<div id="' . $field_name . '-' . $delta . '-link-wrapper">',
      '#suffix' => '</div>',
    ];

    // Remove item button.
    if ($this->cardinality == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) {
      $element['actions'] = [
        '#type' => 'actions',
        'remove_button' => [
          '#delta' => $delta,
          '#name' => implode('_', $parents) . '_remove_button',
          '#type' => 'submit',
          '#value' => $this->t('Remove'),
          '#submit' => [[static::class, 'removeSubmit']],
          '#ajax' => [
            'callback' => [$this, 'ajaxRemove'],
            'effect' => 'fade',
            'wrapper' => $form['#wrapper_id'],
          ],
          '#weight' => 1000,
        ],
      ];
    }

    // Attached library and transferred parameters.
    if ($enable_social) {
      $element['#attached']['library'][] = 'social_link_field/social_link_field.social_select';
      foreach ($this->platforms as $platform) {
        $element['#attached']['drupalSettings']['platforms'][$platform['id']]['prefix'] = $platform['urlPrefix'];
      }
    }

    return $element;
  }

  /**
   * Ajax callback function for update link dynamically.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax response.
   */
  public function updateLinkName(array $form, FormStateInterface $form_state, Request $request) {
    $response = new AjaxResponse();
    $element = $form_state->getTriggeringElement();
    $field_name = $element['#data']['field_name'];
    $delta = $element['#data']['delta'];

    // Get field element on default field settings form.
    if (isset($form['default_value']) && $form['default_value']['widget']['#field_name'] == $field_name) {
      $element_link = $form['default_value']['widget'][$delta]['link'];
    }
    else {
      $element_link = $form[$field_name]['widget'][$delta]['link'];
    }
    $element_link['#field_prefix'] = $this->platforms[$element['#value']]['urlPrefix'];

    $response->addCommand(new ReplaceCommand('#' . $field_name . '-' . $delta . '-link-wrapper', $element_link));

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  protected function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {
    if ((count($items) > 0) && !$form_state->isRebuilding()) {
      $field_name = $this->fieldDefinition->getName();
      $parents = $form['#parents'];

      if ($this->cardinality == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) {
        $field_state = static::getWidgetState($parents, $field_name, $form_state);
        $field_state['items_count']--;
        static::setWidgetState($parents, $field_name, $form_state, $field_state);
      }
    }
    $form['#wrapper_id'] = Html::getUniqueID($items->getName());
    $elements = parent::formMultipleElements($items, $form, $form_state);

    $elements['#prefix'] = '<div id="' . $form['#wrapper_id'] . '">';
    $elements['#suffix'] = '</div>';
    $elements['add_more']['#ajax']['wrapper'] = $form['#wrapper_id'];

    if ($this->getSetting('disable_weight')) {
      // Disable item order change.
      $elements['#theme'] = 'field_multiple_value_no_draggable_form';
    }

    return $elements;
  }

  /**
   * Submit callback to remove an item from the field UI multiple wrapper.
   *
   * @param array $form
   *   The form structure where widgets are being attached to.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function removeSubmit(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $delta = $button['#delta'];
    $address = array_slice($button['#array_parents'], 0, -4);
    $address_state = array_slice($button['#parents'], 0, -3);
    $parent_element = NestedArray::getValue($form, array_merge($address, ['widget']));
    $field_name = $parent_element['#field_name'];
    $parents = $parent_element['#field_parents'];
    $field_state = static::getWidgetState($parents, $field_name, $form_state);

    for ($i = $delta; $i <= $field_state['items_count']; $i++) {
      $old_element_address = array_merge($address, ['widget', $i + 1]);
      $old_element_state_address = array_merge($address_state, [$i + 1]);
      $new_element_state_address = array_merge($address_state, [$i]);
      $moving_element = NestedArray::getValue($form, $old_element_address);
      $moving_element_value = NestedArray::getValue($form_state->getValues(), $old_element_state_address);
      $moving_element_input = NestedArray::getValue($form_state->getUserInput(), $old_element_state_address);
      $moving_element_field = NestedArray::getValue($form_state->get('field_storage'), array_merge(['#parents'], $address));
      $moving_element['#parents'] = $new_element_state_address;
      $form_state->setValueForElement($moving_element, $moving_element_value);
      $user_input = $form_state->getUserInput();
      NestedArray::setValue($user_input, $moving_element['#parents'], $moving_element_input);
      $form_state->setUserInput($user_input);
      NestedArray::setValue($form_state->get('field_storage'), array_merge(['#parents'], $moving_element['#parents']), $moving_element_field);
    }

    if ($field_state['items_count'] > 0) {
      $field_state['items_count']--;
    }

    static::setWidgetState($parents, $field_name, $form_state, $field_state);
    $form_state->setRebuild();
  }

  /**
   * Ajax callback to remove a field collection from a multi-valued field.
   *
   * @param array $form
   *   The form structure where widgets are being attached to.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An AjaxResponse object.
   */
  public function ajaxRemove(array $form, FormStateInterface &$form_state) {
    $button = $form_state->getTriggeringElement();
    $parent = NestedArray::getValue(
      $form,
      array_slice($button['#array_parents'], 0, -3)
    );

    return $parent;
  }

  /**
   * Provide default form values.
   *
   * @param bool $enable_social
   *   Necessity to show or hide social select.
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   Array of default values for this field.
   * @param int $delta
   *   The order of this item in the array of sub-elements.
   *
   * @return string[]
   *   Returns default item values.
   */
  protected function getFormValues($enable_social, FieldItemListInterface $items, $delta) {
    $entity_values = $items[$delta];
    $default_values = $this->fieldDefinition->getDefaultValueLiteral();

    if ($enable_social) {
      if ($entity_values->social) {
        $social = $entity_values->social;
        $link = $entity_values->link;
      }
      else {
        $social = $this->platforms[array_rand($this->platforms, 1)]['id'];
        $link = '';
      }
    }
    else {
      if (isset($default_values[$delta])) {
        $social = $default_values[$delta]['social'];
        $link = $default_values[$delta]['link'];
      }
      else {
        $social = $this->platforms[array_rand($this->platforms, 1)]['id'];
        $link = '';
      }

      if ($entity_values->social == $social) {
        $link = $entity_values->link ?: '';
      }
    }

    return [
      'social' => $social,
      'link' => $link,
    ];
  }

}
