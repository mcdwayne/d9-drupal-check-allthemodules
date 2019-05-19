<?php

namespace Drupal\simple_seo_preview\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'simple_seo_preview_widget_type' widget.
 *
 * @FieldWidget(
 *   id = "simple_seo_preview_widget_type",
 *   label = @Translation("Simple SEO preview form"),
 *   field_types = {
 *     "simple_seo_preview"
 *   }
 * )
 */
class SimpleSeoPreviewWidgetType extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    array $third_party_settings,
    AccountProxyInterface $current_user) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);

    $this->currentUser = $current_user;
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
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'seo_preview_expanded'  => TRUE,
      'title_size'            => 60,
      'title_max_char'        => 60,
      'description_rows'      => 3,
      'description_max_chars' => 155,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];

    $elements['seo_preview_expanded'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Default expand <em>Simple SEO preview</em> field ?'),
      '#default_value' => $this->getSetting('seo_preview_expanded'),
    ];
    $elements['title_size'] = [
      '#type'          => 'number',
      '#title'         => $this->t('Title size'),
      '#default_value' => $this->getSetting('title_size'),
      '#required'      => TRUE,
      '#min'           => 1,
    ];
    $elements['title_max_char'] = [
      '#type'          => 'number',
      '#title'         => $this->t('Title maximum characters'),
      '#default_value' => $this->getSetting('title_max_char'),
      '#required'      => TRUE,
      '#min'           => 1,
    ];
    $elements['description_rows'] = [
      '#type'          => 'number',
      '#title'         => $this->t('Description rows'),
      '#default_value' => $this->getSetting('description_rows'),
      '#required'      => TRUE,
      '#min'           => 1,
    ];
    $elements['description_max_chars'] = [
      '#type'          => 'number',
      '#title'         => $this->t('Description maximum characters'),
      '#default_value' => $this->getSetting('description_max_chars'),
      '#required'      => TRUE,
      '#min'           => 1,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $expanded_seo_preview_text = $this->t('No');
    $is_expanded_seo_preview = $this->getSetting('seo_preview_expanded');
    if (isset($is_expanded_seo_preview) && $is_expanded_seo_preview == TRUE) {
      $expanded_seo_preview_text = $this->t('Yes');
    }

    $summary[] = $this->t('Default expand <em>Simple SEO preview</em> field: @text', ['@text' => $expanded_seo_preview_text]);
    $summary[] = $this->t('Title size: @size', ['@size' => $this->getSetting('title_size')]);
    $summary[] = $this->t('Title max. chars: @size', ['@size' => $this->getSetting('title_max_char')]);
    $summary[] = $this->t('Description rows: @rows', ['@rows' => $this->getSetting('description_rows')]);
    $summary[] = $this->t('Description max. chars: @size', ['@size' => $this->getSetting('description_max_chars')]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $item = $items[$delta];
    $values = [];
    if (!empty($item->value)) {
      $values = unserialize($item->value);
    }

    // Add form validation.
    $element += [
      '#element_validate' => [[get_class($this), 'validateFormElement']],
    ];
    $form_state->setTemporaryValue('title_max_char', $this->getSetting('title_max_char'));
    $form_state->setTemporaryValue('description_max_chars', $this->getSetting('description_max_chars'));

    $element['value']['meta'] = [
      '#type'     => 'details',
      '#title'    => $element['#title'],
      '#open'     => $this->getSetting('seo_preview_expanded'),
      '#access'   => $this->userHasViewPermission(),
    ];
    $element['value']['meta']['title'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Title'),
      '#required'      => isset($element['#required']) ? $element['#required'] : FALSE,
      '#default_value' => isset($values['meta']['title']) ? $values['meta']['title'] : NULL,
      '#size'          => $this->getSetting('title_size'),
      '#access'        => $this->userHasAdministerPermission(),
      '#description'   => $this->t('It is recommended that the title is no greater than @size characters long, including spaces.', [
        '@size' => $this->getSetting('title_max_char'),
      ]),
      '#attributes'    => [
        'class' => ['js--simple_seo_preview-title'],
      ],
    ];
    $element['value']['meta']['description'] = [
      '#type'          => 'textarea',
      '#title'         => $this->t('Description'),
      '#required'      => isset($element['#required']) ? $element['#required'] : FALSE,
      '#default_value' => isset($values['meta']['description']) ? $values['meta']['description'] : NULL,
      '#rows'          => $this->getSetting('description_rows'),
      '#access'        => $this->userHasAdministerPermission(),
      '#description'   => $this->t('It is recommended that the title is no greater than @size characters long, including spaces.', [
        '@size' => $this->getSetting('description_max_chars'),
      ]),
      '#attributes'    => [
        'class' => ['js--simple_seo_preview-description'],
      ],
    ];

    $node = NULL;
    $form_object = $form_state->getFormObject();
    if ($form_object instanceof ContentEntityForm) {
      $node = $form_object->getEntity();
    }

    $element['value']['meta']['overview'] = [
      '#type'   => 'fieldset',
      '#title'  => $this->t('Snippet'),
      '#access' => $this->userHasViewPermission(),
    ];
    $element['value']['meta']['overview']['preview'] = [
      '#theme'       => 'simple_seo_preview_overview',
      '#title'       => isset($values['meta']['title']) ? $values['meta']['title'] : NULL,
      '#url'         => isset($node) && is_numeric($node->id()) ? $node->toUrl('canonical', ['absolute' => TRUE]) : '',
      '#description' => isset($values['meta']['description']) ? $values['meta']['description'] : NULL,
      '#attached'    => [
        'library' => [
          'simple_seo_preview/drupal.simple_seo_preview',
        ],
        'drupalSettings' => [
          'simple_seo_preview' => [
            'title_max_char'        => $this->getSetting('title_max_char'),
            'description_max_chars' => $this->getSetting('description_max_chars'),
          ],
        ],
      ],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as &$value) {
      $flattened_value = [];
      foreach ($value as $group) {
        // Exclude the '_original_delta' value.
        if (is_array($group)) {
          foreach ($group as $key => $field_value) {
            $flattened_value[$key] = $field_value;
          }
        }
      }
      $value = serialize($flattened_value);
    }

    return $values;
  }

  /**
   * Form element validation handler for form element.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function validateFormElement(array &$element, FormStateInterface $form_state) {
    if ($element['#required'] == TRUE) {
      $title = '';
      $description = '';

      $values = $form_state->getValues();
      if (isset($values['field_seo_preview']) && !empty($values['field_seo_preview'])) {
        $title = $values['field_seo_preview'][0]['value']['meta']['title'];
        $description = $values['field_seo_preview'][0]['value']['meta']['description'];
      }

      $title_max_char = $form_state->getTemporaryValue('title_max_char');
      if (is_numeric($title_max_char) && strlen($title) > $title_max_char) {
        $form_state->setError($element['value']['meta']['title'], t('It is recommended that the title is no greater than @size characters long (currently @current_size chars), including spaces.', [
          '@size' => $title_max_char,
          '@current_size' => strlen($title),
        ]));
      }

      $description_max_chars = $form_state->getTemporaryValue('description_max_chars');
      if (is_numeric($description_max_chars) && strlen($description) > $description_max_chars) {
        $form_state->setError($element['value']['meta']['description'], t('It is recommended that the title is no greater than @size characters long (currently @current_size chars), including spaces.', [
          '@size' => $description_max_chars,
          '@current_size' => strlen($description),
        ]));
      }
    }
  }

  /**
   * Check if current user has view permission.
   *
   * @return bool
   *   TRUE if has permission, FALSE otherwise.
   */
  public function userHasViewPermission() {
    if ($this->userHasAdministerPermission()) {
      return TRUE;
    }

    return $this->currentUser->hasPermission('view simple_seo_preview meta tags preview');
  }

  /**
   * Check if current user has administer permission.
   *
   * @return bool
   *   TRUE if has permission, FALSE otherwise.
   */
  public function userHasAdministerPermission() {
    return $this->currentUser->hasPermission('administer simple_seo_preview meta tags');
  }

}
