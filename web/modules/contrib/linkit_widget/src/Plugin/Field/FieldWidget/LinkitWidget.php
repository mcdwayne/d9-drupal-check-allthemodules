<?php

namespace Drupal\linkit_widget\Plugin\Field\FieldWidget;

use Drupal\link\Plugin\Field\FieldWidget\LinkWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'linkit_widget' widget.
 *
 * @FieldWidget(
 *   id = "linkit_widget",
 *   label = @Translation("Linkit"),
 *   field_types = {
 *     "link"
 *   }
 * )
 */
class LinkitWidget extends LinkWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'linkit_profile' => 'default',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  protected static function getUriAsDisplayableString($uri) {
    $scheme = parse_url($uri, PHP_URL_SCHEME);

    // By default, the displayable string is the URI.
    $displayable_string = $uri;

    // A different displayable string may be chosen in case of the 'internal:'
    // or 'entity:' built-in schemes.
    if ($scheme === 'internal') {
      $uri_reference = explode(':', $uri, 2)[1];

      // @todo '<front>' is valid input for BC reasons, may be removed by
      //   https://www.drupal.org/node/2421941
      $path = parse_url($uri, PHP_URL_PATH);
      if ($path === '/') {
        $uri_reference = '<front>' . substr($uri_reference, 1);
      }

      $displayable_string = $uri_reference;
    }
    elseif ($scheme === 'entity' && $entity = static::getEntityFromUri($uri)) {
      $displayable_string = $entity->label();
    }

    return $displayable_string;
  }

  /**
   * Load the entity referenced by an entity scheme uri.
   *
   * @param string $uri
   *   Uri {@inheritdoc}.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   Null {@inheritdoc}.
   */
  protected static function getEntityFromUri($uri) {
    list($entity_type, $entity_id) = explode('/', substr($uri, 7), 2);
    $entity_manager = \Drupal::entityTypeManager();
    if ($entity_manager->getDefinition($entity_type, FALSE)) {
      return $entity_manager->getStorage($entity_type)->load($entity_id);
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public static function validateUriElement($element, FormStateInterface $form_state, $form) {
    if (parse_url($element['#value'], PHP_URL_SCHEME) === 'internal' && !in_array(
      $element['#value'][0], ['/', '?', '#'], TRUE,
      ) && substr($element['#value'], 0, 7) !== '<front>') {
      $form_state->setError($element, t('Manually entered paths should start with /, ? or #.'));
      return;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $item = $items[$delta];
    $uri_as_displayable_string = static::getUriAsDisplayableString($item->uri);
    $linkit_profile_id = $this->getSetting('linkit_profile');

    // The current field value could have been entered by a different user.
    // However, if it is inaccessible to the current user, do not display it
    // to them.
    $default_allowed = !$item->isEmpty() && (\Drupal::currentUser()->hasPermission('link to any page') || $item->getUrl()->access());

    // This is the default field config form link field
    // Used as-is if it is an external link.
    $element['uri'] = [
      '#type' => 'url',
      '#title' => $this->t('URL'),
      '#placeholder' => $this->getSetting('placeholder_url'),
      // The current field value could have been entered by a different user.
      // However, if it is inaccessible to the current user, do not display it
      // to them.
      '#default_value' => ($default_allowed) ? static::getUriAsDisplayableString($item->uri) : NULL,
      '#element_validate' => [[get_called_class(), 'validateUriElement']],
      '#maxlength' => 2048,
      '#required' => $element['#required'],
    ];

    // If the field is configured to support internal links, it cannot use the
    // 'url' form element and we have to do the validation ourselves.
    if ($this->supportsInternalLinks()) {
      $element['uri']['#type'] = 'linkit';
      $element['uri']['#description'] = t('Start typing to find content or paste a URL.');
      $element['uri']['#autocomplete_route_name'] = 'linkit.autocomplete';
      $element['uri']['#autocomplete_route_parameters'] = [
        'linkit_profile_id' => $linkit_profile_id,
      ];

      if ($default_allowed && parse_url($item->uri, PHP_URL_SCHEME) == 'entity') {
        $entity = static::getEntityFromUri($item->uri);
      }

      $element['attributes']['data-entity-type'] = [
        '#type' => 'hidden',
        '#default_value' => $default_allowed && isset($entity) ? $entity->getEntityTypeId() : '',
      ];

      $element['attributes']['data-entity-uuid'] = [
        '#type' => 'hidden',
        '#default_value' => $default_allowed && isset($entity) ? $entity->uuid() : '',
      ];

      $element['attributes']['data-entity-substitution'] = [
        '#type' => 'hidden',
        '#default_value' => $default_allowed && isset($entity) ? $entity->getEntityTypeId() == 'file' ? 'file' : 'canonical' : '',
      ];
    }

    // If the field is configured to allow only internal links, add a useful
    // element prefix and description.
    if (!$this->supportsExternalLinks()) {
      $element['uri']['#field_prefix'] = rtrim(\Drupal::url('<front>', [], ['absolute' => TRUE]), '/');
      $element['uri']['#description'] = $this->t('This must be an internal path such as %add-node. You can also start typing the title of a piece of content to select it. Enter %front to link to the front page.', ['%add-node' => '/node/add', '%front' => '<front>']);
    }
    // If the field is configured to allow both internal and external links,
    // show a useful description.
    elseif ($this->supportsExternalLinks() && $this->supportsInternalLinks()) {
      $element['uri']['#description'] = $this->t('Start typing the title of a piece of content to select it. You can also enter an internal path such as %add-node or an external URL such as %url. Enter %front to link to the front page.', [
        '%front' => '<front>',
        '%add-node' => '/node/add',
        '%url' => 'http://example.com',
      ]);
    }
    // If the field is configured to allow only external links, show a useful
    // description.
    elseif ($this->supportsExternalLinks() && !$this->supportsInternalLinks()) {
      $element['uri']['#description'] = $this->t('This must be an external URL such as %url.', ['%url' => 'http://example.com']);
    }

    $element['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link text'),
      '#placeholder' => $this->getSetting('placeholder_title'),
      '#default_value' => isset($items[$delta]->title) ? $items[$delta]->title : NULL,
      '#maxlength' => 255,
      '#access' => $this->getFieldSetting('title') != DRUPAL_DISABLED,
    ];
    // Post-process the title field to make it conditionally required if URL is
    // non-empty. Omit the validation on the field edit form, since the field
    // settings cannot be saved otherwise.
    if (!$this->isDefaultValueWidget($form_state) && $this->getFieldSetting('title') == DRUPAL_REQUIRED) {
      $element['#element_validate'][] = [get_called_class(), 'validateTitleElement'];
    }

    // If cardinality is 1, ensure a proper label is output for the field.
    if ($this->fieldDefinition->getFieldStorageDefinition()->getCardinality() == 1) {
      // If the link title is disabled, use the field definition label as the
      // title of the 'uri' element.
      if ($this->getFieldSetting('title') == DRUPAL_DISABLED) {
        $element['uri']['#title'] = $element['#title'];
      }
      // Otherwise wrap everything in a details element.
      else {
        $element += [
          '#type' => 'fieldset',
        ];
      }
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $linkit_profiles = \Drupal::entityTypeManager()->getStorage('linkit_profile')->loadMultiple();

    $options = [];
    foreach ($linkit_profiles as $linkit_profile) {
      $options[$linkit_profile->id()] = $linkit_profile->label();
    }

    $elements['linkit_profile'] = [
      '#type' => 'select',
      '#title' => $this->t('Linkit profile'),
      '#options' => $options,
      '#default_value' => $this->getSetting('linkit_profile'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $linkit_profile_id = $this->getSetting('linkit_profile');
    $linkit_profile = \Drupal::entityTypeManager()->getStorage('linkit_profile')->load($linkit_profile_id);

    if ($linkit_profile) {
      $summary[] = $this->t('Linkit profile: @linkit_profile', ['@linkit_profile' => $linkit_profile->label()]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as &$value) {
      $value['uri'] = static::getUriFromSubmittedValue($value);
      $value += ['options' => []];
    }
    return $values;
  }

  /**
   * Converts linkit form fields to a uri.
   *
   * @param array $value
   *   User submitted values for this widget.
   *
   * @return string
   *   String {@inheritdoc}.
   */
  public static function getUriFromSubmittedValue(array $value) {
    $uri = $value['uri'];

    if (empty($uri)) {
      return $uri;
    }

    $entity_type = (!empty($value['attributes']['data-entity-type'])) ? $value['attributes']['data-entity-type'] : NULL;
    $entity_uuid = (!empty($value['attributes']['data-entity-uuid'])) ? $value['attributes']['data-entity-uuid'] : NULL;

    if (!empty($entity_type) && !empty($entity_uuid)) {
      /* @var \Drupal\Core\Entity\EntityInterface $entity */
      $entity = \Drupal::service('entity.repository')->loadEntityByUuid($entity_type, $entity_uuid);
      if ($entity) {
        return 'entity:' . $entity->GetEntityTypeId() . '/' . $entity->id();
      }
    }

    if (!empty($uri) && parse_url($uri, PHP_URL_SCHEME) === NULL) {
      // @todo '<front>' is valid input for BC reasons, may be removed by
      //   https://www.drupal.org/node/2421941
      // - '<front>' -> '/'
      // - '<front>#foo' -> '/#foo'
      if (strpos($uri, '<front>') === 0) {
        $uri = '/' . substr($uri, strlen('<front>'));
      }
      return 'internal:' . $uri;
    }

    return $uri;
  }

}
