<?php

namespace Drupal\private_taxonomy\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Session\AccountInterface;

/**
 * Plugin implementation of the 'private_taxonomy_autocomplete' widget.
 *
 * @FieldWidget(
 *   id = "private_taxonomy_autocomplete",
 *   label = @Translation("Autocomplete private term widget (tagging)"),
 *   field_types = {
 *     "private_taxonomy_term_reference"
 *   },
 *   multiple_values = TRUE
 * )
 */
class PrivateTaxonomyAutocompleteWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  protected $account;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, AccountInterface $account, EntityStorageInterface $term_storage) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);

    $this->account = $account;
    $this->termStorage = $term_storage;
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
      $container->get('current_user'),
      $container->get('entity.manager')->getStorage('taxonomy_term')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'size' => '60',
      'autocomplete_route_name' => 'private_taxonomy.autocomplete',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);
    $element['size'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Size'),
      '#default_value' => $this->getSetting('size'),
      '#description' => $this->t('Size of autocomplete field.'),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $summary[] = $this->t('Textfield size: @size', ['@size' => $this->getSetting('size')]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $tags = [];
    if (!$items->isEmpty()) {
      foreach ($items as $item) {
        $tags[] = isset($item->entity) ? $item->entity : $this->termStorage->load($item->target_id);
      }
    }
    $element += [
      '#type' => 'textfield',
      '#default_value' => taxonomy_implode_tags($tags),
      '#autocomplete_route_name' => $this->getSetting('autocomplete_route_name'),
      '#autocomplete_route_parameters' => [
        'entity_type' => $items->getEntity()->getEntityTypeId(),
        'field_name' => $this->fieldDefinition->getName(),
      ],
      '#size' => $this->getSetting('size'),
      '#maxlength' => 1024,
    ];

    return ['value' => $element];
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $items = [];
    // The $values parameter actually is not an array of terms but is a comma
    // separated string wrapped in an array.
    $form_values = [];
    if (isset($values['value']) && strlen(trim($values['value'])) > 0) {
      $form_values = explode(',', $values['value']);
    }

    $vid = $this->getFieldSetting('allowed_values')[0]['vocabulary'];

    // Autocomplete widgets do not send their tids in the form, so we must
    // detect them here and process them independently.
    foreach ($form_values as $value) {
      $name = trim($value);
      // Remove trailing owner in parenthesis if it exists.
      $matches = [];
      $result = preg_match('/\((.*)\)$/', $name, $matches, PREG_OFFSET_CAPTURE);
      $owner = $this->account;
      if (isset($matches[1])) {
        $user = user_load_by_name($matches[1][0]);
        if ($user) {
          $owner = $user;
        }
        $name = trim(Unicode::substr($name, 0, $matches[0][1]));
      }

      $possibilities = $this->termStorage
        ->loadByProperties([
          'name' => $name,
          'vid' => $vid,
        ]);
      // Remove terms belonging to other users.
      foreach ($possibilities as $key => $possibility) {
        if ($owner->id() != private_taxonomy_term_get_user($possibility->id())) {
          unset($possibilities[$key]);
        }
      }
      // See if the term exists in the chosen vocabulary and return the tid;
      // otherwise, create a new term.
      if (count($possibilities) > 0) {
        $term = array_pop($possibilities);
        $item = ['target_id' => $term->id()];
      }
      else {
        $term = Term::create([
          'vid' => $vid,
          'name' => $name,
        ]);
        $item = ['target_id' => NULL, 'entity' => $term];
      }
      $items[] = $item;
    }

    return $items;
  }

}
