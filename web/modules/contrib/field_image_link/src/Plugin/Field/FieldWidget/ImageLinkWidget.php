<?php

namespace Drupal\field_image_link\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\field_image_link\ImageLinkItemInterface;
use Drupal\file\Entity\File;
use Drupal\image\Plugin\Field\FieldWidget\ImageWidget;

/**
 * Plugin implementation of the 'image_link' widget.
 *
 * @FieldWidget(
 *   id = "image_link",
 *   label = @Translation("Image with Link"),
 *   field_types = {
 *     "image_link"
 *   }
 * )
 */
class ImageLinkWidget extends ImageWidget {

  /**
   * Indicates enabled support for link to routes.
   *
   * @return bool
   *   Returns TRUE if the LinkItem field is configured to support links to
   *   routes, otherwise FALSE.
   */
  protected function supportsInternalLinks() {
    $link_type = $this->getFieldSetting('link_type');
    return (bool) ($link_type & ImageLinkItemInterface::LINK_INTERNAL);
  }

  /**
   * Indicates enabled support for link to external URLs.
   *
   * @return bool
   *   Returns TRUE if the LinkItem field is configured to support links to
   *   external URLs, otherwise FALSE.
   */
  protected function supportsExternalLinks() {
    $link_type = $this->getFieldSetting('link_type');
    return (bool) ($link_type & ImageLinkItemInterface::LINK_EXTERNAL);
  }

  /**
   * Gets the URI without the 'internal:' or 'entity:' scheme.
   *
   * The following two forms of URIs are transformed:
   * - 'entity:' URIs: to entity autocomplete ("label (entity id)") strings;
   * - 'internal:' URIs: the scheme is stripped.
   *
   * This method is the inverse of ::getUserEnteredStringAsUri().
   *
   * @param string $uri
   *   The URI to get the displayable string for.
   *
   * @return string
   *
   * @see static::getUserEnteredStringAsUri()
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
    elseif ($scheme === 'entity') {
      list($entity_type, $entity_id) = explode('/', substr($uri, 7), 2);
      // Show the 'entity:' URI as the entity autocomplete would.
      // @todo Support entity types other than 'node'. Will be fixed in
      //    https://www.drupal.org/node/2423093.
      if ($entity_type == 'node' && $entity = \Drupal::entityTypeManager()->getStorage($entity_type)->load($entity_id)) {
        $displayable_string = EntityAutocomplete::getEntityLabels([$entity]);
      }
    }

    return $displayable_string;
  }

  /**
   * Gets the user-entered string as a URI.
   *
   * The following two forms of input are mapped to URIs:
   * - entity autocomplete ("label (entity id)") strings: to 'entity:' URIs;
   * - strings without a detectable scheme: to 'internal:' URIs.
   *
   * This method is the inverse of ::getUriAsDisplayableString().
   *
   * @param string $string
   *   The user-entered string.
   *
   * @return string
   *   The URI, if a non-empty $uri was passed.
   *
   * @see static::getUriAsDisplayableString()
   */
  protected static function getUserEnteredStringAsUri($string) {
    // By default, assume the entered string is an URI.
    $uri = $string;

    // Detect entity autocomplete string, map to 'entity:' URI.
    $entity_id = EntityAutocomplete::extractEntityIdFromAutocompleteInput($string);
    if ($entity_id !== NULL) {
      // @todo Support entity types other than 'node'. Will be fixed in
      //    https://www.drupal.org/node/2423093.
      $uri = 'entity:node/' . $entity_id;
    }
    // Detect a schemeless string, map to 'internal:' URI.
    elseif (!empty($string) && parse_url($string, PHP_URL_SCHEME) === NULL) {
      // @todo '<front>' is valid input for BC reasons, may be removed by
      //   https://www.drupal.org/node/2421941
      // - '<front>' -> '/'
      // - '<front>#foo' -> '/#foo'
      if (strpos($string, '<front>') === 0) {
        $string = '/' . substr($string, strlen('<front>'));
      }
      $uri = 'internal:' . $string;
    }

    return $uri;
  }

  /**
   * Form element validation handler for the 'uri' element.
   *
   * Disallows saving inaccessible or untrusted URLs.
   *
   * @param $element
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param $form
   */
  public static function validateLinkUriElement($element, FormStateInterface $form_state, $form) {
    // Only do validation if the function is triggered from other places than
    // the image process form.
    $triggering_element = $form_state->getTriggeringElement();
    if (empty($triggering_element['#submit']) || !in_array('file_managed_file_submit', $triggering_element['#submit'])) {
      $uri = static::getUserEnteredStringAsUri($element['#value']);
      $form_state->setValueForElement($element, $uri);

      // If getUserEnteredStringAsUri() mapped the entered value to a 'internal:'
      // URI , ensure the raw value begins with '/', '?' or '#'.
      // @todo '<front>' is valid input for BC reasons, may be removed by
      //   https://www.drupal.org/node/2421941
      if (parse_url($uri, PHP_URL_SCHEME) === 'internal' && !in_array($element['#value'][0], ['/', '?', '#'], TRUE) && substr($element['#value'], 0, 7) !== '<front>') {
        $form_state->setError($element, t('Manually entered paths should start with /, ? or #.'));
        return;
      }
    }
    else {
      $form_state->setLimitValidationErrors([]);
    }
  }

  /**
   * Form element validation handler for the link type.
   *
   * @param $element
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param $form
   */
  public static function validateLinkTypeElement($element, FormStateInterface $form_state, $form) {
    // Only do validation if the function is triggered from other places than
    // the image process form.
    $triggering_element = $form_state->getTriggeringElement();
    if (empty($triggering_element['#submit']) || !in_array('file_managed_file_submit', $triggering_element['#submit'])) {
      $uri = static::getUserEnteredStringAsUri($element['#value']);

      if (!$element['#required'] && $uri == '') {
        return;
      }

      $uri_is_valid = TRUE;
      $link_type = $element['#link_type'];

      // Try to resolve the given URI to a URL. It may fail if it's schemeless.
      try {
        $url = Url::fromUri($uri);
      }
      catch (\InvalidArgumentException $e) {
        $uri_is_valid = FALSE;
      }

      // If the link field doesn't support both internal and external links,
      // check whether the URL (a resolved URI) is in fact violating either
      // restriction.
      if ($uri_is_valid && $link_type !== ImageLinkItemInterface::LINK_GENERIC) {
        if (!($link_type & ImageLinkItemInterface::LINK_EXTERNAL) && $url->isExternal()) {
          $uri_is_valid = FALSE;
        }
        if (!($link_type & ImageLinkItemInterface::LINK_INTERNAL) && !$url->isExternal()) {
          $uri_is_valid = FALSE;
        }
      }

      if (!$uri_is_valid) {
        $form_state->setError($element, t('The path \'@uri\' is invalid.'));
        return;
      }
    }
    else {
      $form_state->setLimitValidationErrors([]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $element['#link_type'] = $this->getFieldSetting('link_type');
    $element['#link_title'] = $this->getFieldSetting('link_title');
    $element['#link_supports_internal_links'] = $this->supportsInternalLinks();
    $element['#link_supports_external_links'] = $this->supportsExternalLinks();

    // Switch file_validate_is_image to file_validate_extensions.
    unset($element['#upload_validators']['file_validate_is_image']);
    // If not using custom extension validation, ensure this is an image.
    $field_settings = $this->getFieldSettings();
    $supportedExtensions = ['png', 'gif', 'jpg', 'jpeg', 'svg'];
    $extensions = $field_settings['file_extensions'];
    $extensions = array_intersect(explode(' ', $extensions), $supportedExtensions);
    $element['#upload_validators']['file_validate_extensions'][0] = implode(' ', $extensions);

    return $element;
  }

  /**
   * Form API callback: Processes a image_image field element.
   *
   * Expands the image_image type to include the alt and title fields.
   *
   * This method is assigned as a #process callback in formElement() method.
   */
  public static function process($element, FormStateInterface $form_state, $form) {
    $item = $element['#value'];

    $element['link_uri'] = [
      '#type' => 'url',
      '#title' => t('Link URL'),
      // The current field value could have been entered by a different user.
      // However, if it is inaccessible to the current user, do not display it
      // to them.
      '#default_value' => isset($item['link_uri']) && (\Drupal::currentUser()->hasPermission('link to any page') || Url::fromUri($item['link_uri'])->access()) ? static::getUriAsDisplayableString($item['link_uri']) : '',
      '#element_validate' => [
        [get_called_class(), 'validateLinkTypeElement'],
        [get_called_class(), 'validateLinkUriElement'],
      ],
      '#maxlength' => 2048,
      '#required' => $element['#required'],
      '#link_type' => $element['#link_type'],
      '#access' => (bool) $item['fids'],
      '#weight' => -5.5,
    ];

    // If the field is configured to support internal links, it cannot use the
    // 'url' form element and we have to do the validation ourselves.
    if ($element['#link_supports_internal_links']) {
      $element['link_uri']['#type'] = 'entity_autocomplete';
      // @todo The user should be able to select an entity type. Will be fixed
      //    in https://www.drupal.org/node/2423093.
      $element['link_uri']['#target_type'] = 'node';
      // Disable autocompletion when the first character is '/', '#' or '?'.
      $element['link_uri']['#attributes']['data-autocomplete-first-character-blacklist'] = '/#?';

      // The link widget is doing its own processing in
      // static::getUriAsDisplayableString().
      $element['link_uri']['#process_default_value'] = FALSE;
    }

    // If the field is configured to allow only internal links, add a useful
    // element prefix and description.
    if (!$element['#link_supports_external_links']) {
      $element['link_uri']['#field_prefix'] = rtrim(Url::fromRoute('<front>', [], ['absolute' => TRUE]), '/');
      $element['link_uri']['#description'] = t('This must be an internal path such as %add-node. You can also start typing the title of a piece of content to select it. Enter %front to link to the front page.', ['%add-node' => '/node/add', '%front' => '<front>']);
    }
    // If the field is configured to allow both internal and external links,
    // show a useful description.
    elseif ($element['#link_supports_external_links'] && $element['#link_supports_internal_links']) {
      $element['link_uri']['#description'] = t('Start typing the title of a piece of content to select it. You can also enter an internal path such as %add-node or an external URL such as %url. Enter %front to link to the front page.', ['%front' => '<front>', '%add-node' => '/node/add', '%url' => 'http://example.com']);
    }
    // If the field is configured to allow only external links, show a useful
    // description.
    elseif ($element['#link_supports_external_links'] && !$element['#link_supports_internal_links']) {
      $element['link_uri']['#description'] = t('This must be an external URL such as %url.', ['%url' => 'http://example.com']);
    }

    $element['link_title'] = [
      '#type' => 'textfield',
      '#title' => t('Link text'),
      '#default_value' => isset($item['link_title']) ? $item['link_title'] : '',
      '#maxlength' => 255,
      '#access' => (bool) $item['fids'] && $element['#link_title'] != DRUPAL_DISABLED,
      '#required' => $element['#link_title'] === DRUPAL_REQUIRED && $element['#required'],
      '#weight' => -5.4,
    ];

    if ($element['link_title'] === DRUPAL_REQUIRED) {
      $element['#element_validate'][] = [get_called_class(), 'validateRequiredFields'];
    }

    $element['link_display_settings'] = [
      '#type' => 'container',
      '#tree' => TRUE,
      '#weight' => -5.3,
      '#access' => (bool) $item['fids'] && $element['#link_title'] != DRUPAL_DISABLED,
    ];

    $element['link_display_settings']['formatter_settings'] = [
      '#type' => 'checkbox',
      '#title' => t('Override formatter settings'),
      '#default_value' => isset($item['link_display_settings']['formatter_settings']) ? $item['link_display_settings']['formatter_settings'] : FALSE,
    ];

    $link_types = [
      'content' => t('Content'),
      'file' => t('File'),
      'link' => t('Entered link'),
    ];
    $element['link_display_settings']['image_link'] = [
      '#title' => t('Link image to'),
      '#type' => 'select',
      '#default_value' => isset($item['link_display_settings']['image_link']) ? $item['link_display_settings']['image_link'] : NULL,
      '#empty_option' => t('Nothing'),
      '#options' => $link_types,
      '#states' => [
        'visible' => [
          ':input[name*="formatter_settings"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Display link title settings.
    $element['link_display_settings']['link_title_display'] = [
      '#type' => 'checkbox',
      '#title' => t('Display linked title'),
      '#default_value' => isset($item['link_display_settings']['link_title_display']) ? $item['link_display_settings']['link_title_display'] : FALSE,
      '#states' => [
        'visible' => [
          ':input[name*="formatter_settings"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Link title tag settings.
    $title_tags = [
      'div' => 'DIV',
      'span' => 'SPAN',
      'p' => 'P',
      'h1' => 'H1',
      'h2' => 'H2',
      'h3' => 'H3',
      'h4' => 'H4',
      'h5' => 'H5',
      'h6' => 'H6',
    ];

    $element['link_display_settings']['link_title_tag'] = [
      '#title' => t('Link title tag'),
      '#type' => 'select',
      '#default_value' => isset($item['link_display_settings']['link_title_tag']) ? $item['link_display_settings']['link_title_tag'] : NULL,
      '#empty_option' => t('Select tag'),
      '#options' => $title_tags,
      '#states' => [
        'visible' => [
          ':input[name*="formatter_settings"]' => ['checked' => TRUE],
          ':input[name*="link_title_display"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Link title position settings.
    $title_positions = [
      'before' => 'Before',
      'after' => 'After',
    ];

    $element['link_display_settings']['link_title_position'] = [
      '#title' => t('Link title position'),
      '#type' => 'select',
      '#default_value' => isset($item['link_display_settings']['link_title_position']) ? $item['link_display_settings']['link_title_position'] : NULL,
      '#empty_option' => t('Select position'),
      '#options' => $title_positions,
      '#states' => [
        'visible' => [
          ':input[name*="formatter_settings"]' => ['checked' => TRUE],
          ':input[name*="link_title_display"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $element['link_display_settings']['link_rel'] = [
      '#type' => 'checkbox',
      '#title' => t('Add rel="nofollow" to links'),
      '#return_value' => 'nofollow',
      '#default_value' => isset($item['link_display_settings']['link_rel']) ? $item['link_display_settings']['link_rel'] : FALSE,
      '#states' => [
        'visible' => [
          ':input[name*="formatter_settings"]' => ['checked' => TRUE],
          ':input[name*="link_title_display"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $element['link_display_settings']['link_target'] = [
      '#type' => 'checkbox',
      '#title' => t('Open link in new window'),
      '#return_value' => '_blank',
      '#default_value' => isset($item['link_display_settings']['link_target']) ? $item['link_display_settings']['link_target'] : FALSE,
      '#states' => [
        'visible' => [
          ':input[name*="formatter_settings"]' => ['checked' => TRUE],
          ':input[name*="link_title_display"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Exposing the attributes array in the widget is left for alternate and more
    // advanced field widgets.
    $element['link_attributes'] = [
      '#type' => 'value',
      '#tree' => TRUE,
      '#value' => isset($item['link_attributes']) ? $item['link_attributes'] : [],
      '#attributes' => ['class' => ['link-field-widget-attributes']],
      '#weight' => -5.2,
    ];

    $element = parent::process($element, $form_state, $form);

    // Add the image preview.
    if (!empty($element['#files']) && $element['#preview_image_style']) {
      $file = reset($element['#files']);

      $variables = field_image_link_get_image_file_dimensions($file);

      $variables['style_name'] = $element['#preview_image_style'];
      $variables['uri'] = $file->getFileUri();

      // Add a custom preview for SVG file.
      if (field_image_link_is_file_svg($file)) {
        $element['preview'] = [
          '#weight' => -10,
          '#theme' => 'image',
          '#width' => $variables['width'],
          '#height' => $variables['height'],
          '#uri' => $variables['uri'],
        ];
      }
      else {
        $element['preview'] = [
          '#weight' => -10,
          '#theme' => 'image_style',
          '#width' => $variables['width'],
          '#height' => $variables['height'],
          '#style_name' => $variables['style_name'],
          '#uri' => $variables['uri'],
        ];
      }

      // Store the dimensions in the form so the file doesn't have to be
      // accessed again. This is important for remote files.
      $element['width'] = [
        '#type' => 'hidden',
        '#value' => $variables['width'],
      ];
      $element['height'] = [
        '#type' => 'hidden',
        '#value' => $variables['height'],
      ];
    }
    elseif (!empty($element['#default_image'])) {
      $defaultImage = $element['#default_image'];
      $file = File::load($defaultImage['fid']);
      if (!empty($file)) {
        $element['preview'] = [
          '#weight' => -10,
          '#theme' => 'image_style',
          '#width' => $defaultImage['width'],
          '#height' => $defaultImage['height'],
          '#style_name' => $element['#preview_image_style'],
          '#uri' => $file->getFileUri(),
        ];
      }
    }

    return $element;
  }

  /**
   * Form API callback. Retrieves the value for the image_link field element.
   * This method is assigned as a #value_callback in formElement() method.
   *
   * @param $element
   * @param $input
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array|null
   */
  public static function value($element, $input, FormStateInterface $form_state) {
    $return = parent::value($element, $input, $form_state);

    // Set link uri to NULL if it's empty to avoid fails with validation.
    if ($input && !isset($input['link_uri'])) {
      $return['link_uri'] = NULL;
    }
    elseif (isset($input['link_uri']) && $input['link_uri'] == '') {
      $return['link_uri'] = NULL;
    }

    // Set link title to NULL if it's empty to avoid fails with validation.
    if ($input && !isset($input['link_title'])) {
      $return['link_title'] = NULL;
    }
    elseif (isset($input['link_title']) && $input['link_title'] == '') {
      $return['link_title'] = NULL;
    }

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $values = parent::massageFormValues($values, $form, $form_state);

    foreach ($values as &$value) {
      // Normalize link uri to have appropriate array instead of string.
      if (isset($value['link_uri']) && !empty($value['link_uri'])) {
        $value['link_uri'] = static::getUserEnteredStringAsUri($value['link_uri']);
        $value += ['link_options' => []];
      }
      else {
        // Set link uri to NULL if it's empty to avoid fails with validation.
        $value['link_uri'] = NULL;
      }
    }
    return $values;
  }

}
