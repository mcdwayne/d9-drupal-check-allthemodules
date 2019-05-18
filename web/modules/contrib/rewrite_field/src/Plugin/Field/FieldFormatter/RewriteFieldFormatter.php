<?php

namespace Drupal\rewrite_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\rewrite_field\TransformCaseManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\UrlHelper;

/**
 * Plugin implementation of the 'prefix_suffix' formatter.
 *
 * @FieldFormatter(
 *   id = "rewrite_field",
 *   label = @Translation("Rewrite Field"),
 *   field_types = {
 *     "string",
 *     "text",
 *     "text_long",
 *     "text_with_summary"
 *   },
 *   settings = {
 *     "prefix" = "",
 *     "suffix" = "",
 *     "custom_text" = "",
 *     "make_link" = FALSE,
 *     "link_path" = "",
 *     "external_link" = FALSE,
 *     "link_options" = "",
 *     "link_attributes" = "",
 *     "text_case" = ""
 *   }
 * )
 */
class RewriteFieldFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * Transform case plugin manager.
   *
   * @var \Drupal\rewrite_field\TransformCaseManager
   */
  protected $transformCaseManager;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'prefix' => '',
      'suffix' => '',
      'custom_text' => '',
      'make_link' => FALSE,
      'link_path' => '',
      'external_link' => FALSE,
      'link_options' => '',
      'link_attributes' => '',
      'text_case' => 'none',
    ) + parent::defaultSettings();
  }

  /**
   * RewriteFieldFormatter constructor.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, TransformCaseManager $transform_case_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->transformCaseManager = $transform_case_manager;
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
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('rewrite_field.transform_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $element['prefix'] = array(
      '#title' => $this->t('Prefix'),
      '#type' => 'textfield',
      '#size' => 20,
      '#default_value' => $this->settings['prefix'],
    );

    if (\Drupal::moduleHandler()->moduleExists('token')) {
      $element['tokens_prefix'] = array(
        '#theme' => 'token_tree_link',
        '#token_types' => ['node'],
        '#global_types' => TRUE,
        '#click_insert' => TRUE,
      );
    }

    $element['suffix'] = array(
      '#title' => $this->t('Suffix'),
      '#type' => 'textfield',
      '#size' => 20,
      '#default_value' => $this->settings['suffix'],
    );

    if (\Drupal::moduleHandler()->moduleExists('token')) {
      $element['tokens_suffix'] = array(
        '#theme' => 'token_tree_link',
        '#token_types' => ['node'],
        '#global_types' => TRUE,
        '#click_insert' => TRUE,
      );
    }

    $element['custom_text'] = array(
      '#title' => $this->t('Custom Text'),
      '#type' => 'textarea',
      '#description' => $this->t('Override the output of this field with custom text'),
      '#default_value' => $this->settings['custom_text'],
    );

    if (\Drupal::moduleHandler()->moduleExists('token')) {
      $element['tokens_custom_text'] = array(
        '#theme' => 'token_tree_link',
        '#token_types' => ['node'],
        '#global_types' => TRUE,
        '#click_insert' => TRUE,
      );
    }
    else {
      $element['token']['token_help'] = array(
        '#markup' => '<p>' . t('Enable the <a href="@drupal-token">Token module</a> to view the available token browser.', array('@drupal-token' => 'http://drupal.org/project/token')) . '</p>',
      );
    }

    $element['make_link'] = array(
      '#title' => $this->t('Output this field as a custom link'),
      '#type' => 'checkbox',
      '#default_value' => $this->settings['make_link'],
    );

    $element['link_path'] = array(
      '#title' => $this->t('Link path'),
      '#type' => 'textfield',
      '#default_value' => $this->settings['link_path'],
      '#description' => $this->t('The Drupal path (/node) or absolute URL (http://www.example.com) for this link.'),
      '#states' => array(
        'visible' => array(
          ':input[name$="[settings_edit_form][settings][make_link]"]' => array('checked' => TRUE),
        ),
      ),
      '#maxlength' => 255,
    );

    $element['external_link'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('External URL'),
      '#default_value' => $this->settings['external_link'],
      '#description' => $this->t("A link to external server: e.g. 'http://www.example.com' or 'www.example.com'."),
      '#states' => array(
        'visible' => array(
          ':input[name$="[settings_edit_form][settings][make_link]"]' => array('checked' => TRUE),
        ),
      ),
    );

    $element['link_options'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Link Options'),
      '#default_value' => $this->settings['link_options'],
      '#description' => $this->t("Comma seperated list of options. Like, query=key|value,fragment=1234,absolute=true"),
      '#states' => array(
        'visible' => array(
          ':input[name$="[settings_edit_form][settings][make_link]"]' => array('checked' => TRUE),
        ),
      ),
    );

    $element['link_attributes'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Link Attributes'),
      '#default_value' => $this->settings['link_attributes'],
      '#description' => $this->t("Comma seperated list of attributes. Like, class=myClass1 myClass2,rel=lightbox,target=_blank"),
      '#states' => array(
        'visible' => array(
          ':input[name$="[settings_edit_form][settings][make_link]"]' => array('checked' => TRUE),
        ),
      ),
    );

    $plugin_definitions = $this->transformCaseManager->getDefinitions();
    $plugins = array();
    foreach ($plugin_definitions as $plugin_id => $plugin) {
      $plugins[$plugin_id] = $plugin['title'];
    }
    $element['text_case'] = array(
      '#type' => 'select',
      '#title' => $this->t('Transform the text case'),
      '#description' => $this->t('Transform the case of the text.'),
      '#options' => ['none' => t('No Transform')] + $plugins,
      '#default_value' => $this->settings['text_case'],
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode = NULL) {
    $elements = array();
    $settings = $this->settings;

    // Allow other modules to alter the settings that have been saved per Field.
    \Drupal::moduleHandler()->alter('rewrite_settings', $items, $settings);

    $prefix = $settings['prefix'];
    $suffix = $settings['suffix'];
    $custom_text = $settings['custom_text'];
    $link_path = strip_tags($settings['link_path']);
    $text_case = $settings['text_case'];

    $node = $items->getEntity();
    $token_service = \Drupal::token();
    foreach ($items as $delta => $item) {
      $output = $item->value;
      if (!empty($output) && !empty($custom_text)) {
        // Token Replacement.
        $output = $token_service->replace($custom_text, array('node' => $node));
      }
      if ($text_case != 'none') {
        $plugin = $this->transformCaseManager->getDefinition($text_case);
        $output = $plugin['class']::transform($output);
      }
      if ($settings['make_link'] && !empty($link_path)) {
        $output = $this->makeLink($token_service->replace($link_path, array('node' => $node)), $output);
      }
      if (!empty($prefix)) {
        $output = $token_service->replace($prefix, array('node' => $node)) . $output;
      }
      if (!empty($suffix)) {
        $output = $output . $token_service->replace($suffix, array('node' => $node));
      }
      $elements[$delta] = [
        '#markup' => $output,
      ];
    }
    return $elements;
  }

  /**
   * Generate the output as a link, with inputs from user.
   */
  protected function makeLink($link_path, $output) {
    $link_options = array();
    if (!empty($this->settings['link_options'])) {
      $link_options = $this->createArray($this->settings['link_options']);
    }
    if (isset($this->settings['link_attributes'])) {
      $link_options['attributes'] = $this->createArray($this->settings['link_attributes']);
    }

    if (UrlHelper::isExternal($link_path) || !empty($this->settings['external_link'])) {
      $url = Url::fromUri($link_path, $link_options);
    }
    else {
      // Add a slash in front of user provided path.
      $link_path = (strpos($link_path, '/') !== 0) ? '/' . $link_path : $link_path;
      $url = Url::fromUserInput($link_path, $link_options);
    }

    return Link::fromTextAndUrl($output, $url)->toString();
  }

  /**
   * Create the array, parsable to core methods.
   */
  protected function createArray($string) {
    $values = array();
    $explodedValues = explode(',', $string);
    foreach ($explodedValues as $explodedValue) {
      $explodedVal = explode('=', $explodedValue);
      // Handle "query" case.
      if ($explodedVal[0] == 'query') {
        $queryValues = explode('&', $explodedVal[1]);
        foreach ($queryValues as $queryValue) {
          $queryVal = explode('|', $queryValue);
          $values[$explodedVal[0]][$queryVal[0]] = $queryVal[1];
        }
      }
      elseif ($explodedVal[0] == 'class') {
        $values[$explodedVal[0]] = explode(' ', $explodedVal[1]);
      }
      else {
        $values[$explodedVal[0]] = $explodedVal[1];
      }
    }
    return $values;
  }

}
