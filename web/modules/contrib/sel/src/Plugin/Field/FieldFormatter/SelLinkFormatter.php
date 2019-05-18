<?php

namespace Drupal\sel\Plugin\Field\FieldFormatter;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\link\Plugin\Field\FieldFormatter\LinkFormatter;
use Drupal\sel\SelSpamspanSettingsFormTrait;
use Egulias\EmailValidator\EmailValidator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'sel_link' formatter.
 *
 * @FieldFormatter(
 *   id = "sel_link",
 *   label = @Translation("Safe external link"),
 *   field_types = {
 *     "link"
 *   }
 * )
 */
class SelLinkFormatter extends LinkFormatter {
  use SelSpamspanSettingsFormTrait;

  /**
   * The path validator service.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * The email validator.
   *
   * @var \Egulias\EmailValidator\EmailValidator
   */
  protected $emailValidator;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('path.validator'),
      $container->get('email.validator'),
      $container->get('module_handler')
    );
  }

  /**
   * Constructs a SelLinkFormatter instance.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Third party settings.
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   *   The path validator service.
   * @param \Egulias\EmailValidator\EmailValidator $email_validator
   *   The email validator service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, PathValidatorInterface $path_validator, EmailValidator $email_validator, ModuleHandlerInterface $module_handler) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $path_validator);
    $this->pathValidator = $path_validator;
    $this->emailValidator = $email_validator;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $conf = \Drupal::config('sel.settings');
    $rel_setting = $conf->get('link_fields.rel_default');
    $rel = !empty($rel_setting) && in_array($rel_setting, array_keys(_sel_rel_defaults())) ?
      $rel_setting : 'noreferrer';
    $rel_optionals = $conf->get('link_fields.rel_optionals_default') ?: [];

    return [
      'sel' => [
        'auto_target' => TRUE,
        'external_rel' => $rel,
        'external_rel_optionals' => $rel_optionals,
        'sanitize_email' => \Drupal::service('module_handler')->moduleExists('spamspan'),
      ],
    ] + SelSpamspanSettingsFormTrait::spamspanDefaultSettings() + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    $sel_settings = $this->getSetting('sel') ?: [];

    $form['sel'] = [
      '#type' => 'details',
      '#title' => $this->t('Safe External Links enhancements'),
      '#open' => TRUE,
      '#weight' => 0,
    ];

    $form['sel']['auto_target'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Open external links in new window'),
      '#default_value' => empty($sel_settings['auto_target']) ? 0 : 1,
      '#states' => [
        'visible' => [
          ':input[name$="[settings][target]"]' => [
            'checked' => FALSE,
          ],
        ],
      ],
    ];

    $form['target']['#states']['visible'] = [
      ':input[name$="[settings][sel][auto_target]"]' => [
        'checked' => FALSE,
      ],
    ];

    $form['sel']['external_rel'] = [
      '#type' => 'select',
      '#title' => $this->t('Required rel attribute value for external links'),
      '#description' => $this->t('One of these rel values are required for protecting the `window` object of this site'),
      '#default_value' => !empty($sel_settings['external_rel']) ? $sel_settings['external_rel'] : 'noreferrer',
      '#required' => TRUE,
      '#options' => _sel_rel_defaults(),
      '#states' => [
        'visible' => [
          ':input[name$="[settings][sel][auto_target]"]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];

    $form['sel']['external_rel_optionals'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Optional rel attributes for external links'),
      '#description' => $this->t('These rel values are optional. Some validators may report invalidity even if the attribute value is valid.'),
      '#default_value' => !empty($sel_settings['external_rel_optionals']) && is_array($sel_settings['external_rel_optionals']) ? $sel_settings['external_rel_optionals'] : [],
      '#options' => _sel_rel_optionals(),
      '#states' => [
        'visible' => [
          ':input[name$="[settings][sel][auto_target]"]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];

    $form['sel']['sanitize_email'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Sanitize email addresses with Spamspan'),
      '#default_value' => empty($sel_settings['sanitize_email']) ? 0 : 1,
    ];

    if (!$this->moduleHandler->moduleExists('spamspan')) {
      $form['sel']['sanitize_email']['#value'] = 0;
      $form['sel']['sanitize_email']['#type'] = 'hidden';
      $form['sel']['spamspan_message'] = [
        '#type' => 'item',
        '#markup' => $this->t('<a href=":spamspan-project">SpamSpan</a> module is unavailable. Install it if you want Safe External Links to obfuscate emails in link fields.', [
          ':spamspan-project' => 'https://www.drupal.org/project/spamspan',
        ]),
      ];
    }

    $form['spamspan'] = $this->spamspanSettings();
    $form['spamspan']['#type'] = 'details';
    $form['spamspan']['#title'] = $this->t('SpamSpan settings');
    $form['spamspan']['#open'] = $this->moduleHandler->moduleExists('spamspan');
    $form['spamspan']['#states']['visible'] = [
      ':input[name$="[settings][sel][sanitize_email]"]' => [
        'checked' => TRUE,
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $settings = $this->getSettings();
    $sel_settings = $settings['sel'];

    if (!empty($sel_settings['auto_target']) && empty($settings['target'])) {
      $summary[] = $this->t('External links open new window');
    }

    if (
      !empty($sel_settings['external_rel_optionals']) ||
      !empty($sel_settings['external_rel'])
    ) {
      $rels = array_filter($sel_settings['external_rel_optionals']);
      array_unshift($rels, $sel_settings['external_rel']);

      $summary[] = $this->t('Rel %auto-rel-values added if the link is external', [
        '%auto-rel-values' => implode(', ', $rels),
      ]);
    }

    if (
      !empty($sel_settings['sanitize_email']) &&
      $this->moduleHandler->moduleExists('spamspan')
    ) {
      $summary[] = $this->t('Email addresses are sanitized');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);
    $settings = $this->getSettings();
    $rel = !empty($settings['sel']['external_rel']) && in_array($settings['sel']['external_rel'], array_keys(_sel_rel_defaults())) ?
      $settings['sel']['external_rel'] : 'noreferrer';
    $rel_values = !empty($settings['sel']['external_rel_optionals']) && is_array($settings['sel']['external_rel_optionals']) ?
      array_filter($settings['sel']['external_rel_optionals']) : [];
    array_unshift($rel_values, $rel);

    foreach ($elements as $delta => $element) {
      $uri_string = !empty($element['#url']) ?
        $element['#url']->toUriString() : '';

      if (
        !empty($element['#url']) &&
        $element['#url']->isExternal() &&
        _sel_uri_is_external($uri_string)
      ) {

        if ($settings['sel']['auto_target']) {
          $elements[$delta]['#options']['attributes']['target'] = '_blank';

          $elements[$delta]['#options']['attributes']['rel'] = empty($element['#options']) || empty($element['#options']['attributes']) || empty($element['#options']['attributes']['rel']) ?
            '' : $element['#options']['attributes']['rel'];

          foreach ($rel_values as $rel_value) {
            if (strpos($elements[$delta]['#options']['attributes']['rel'], $rel_value) === FALSE) {
              $elements[$delta]['#options']['attributes']['rel'] = empty($elements[$delta]['#options']['attributes']['rel']) ?
                $rel_value :
                $elements[$delta]['#options']['attributes']['rel'] . ' ' . $rel_value;
            }
          }
        }
      }

      if (
        $settings['sel']['sanitize_email'] &&
        $this->moduleHandler->moduleExists('spamspan') &&
        strpos($uri_string, 'mailto:') !== FALSE &&
        $this->emailValidator->isValid(str_replace('mailto:', '', $uri_string))
      ) {
        $elements[$delta] = [
          '#theme' => 'sel_spamspan',
          '#email' => str_replace('mailto:', '', $uri_string),
          '#settings' => _sel_array_flattener($settings['spamspan']),
        ];

        if (
          !empty($element['#title']) &&
          $uri_string !== $element['#title'] &&
          str_replace('mailto:', '', $uri_string) !== $element['#title']
        ) {
          $elements[$delta]['#title'] = $element['#title'];
        }
      }

      // Hack cache tags onto the items if the url is internal.
      if (
        !empty($element['#url']) &&
        $element['#url']->isRouted()
      ) {

        $route_name = $element['#url']->getRouteName();

        if (
          strpos($route_name, 'entity.') === 0 &&
          strpos($route_name, '.canonical')
        ) {

          $route_params = $element['#url']->getRouteParameters();
          // Now apply the "hack".
          foreach ($route_params as $key => $value) {
            $elements[$delta]['#cache']['tags'][] = (string) $key . ':' . (string) $value;
          }
        }
      }
    }

    return $elements;
  }

}
