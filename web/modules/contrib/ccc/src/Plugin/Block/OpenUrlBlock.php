<?php

namespace Drupal\ccc\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Utility\Token;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Provides a 'OpenUrlBlock' block.
 *
 * @Block(
 *  id = "ccc_openurl_block",
 *  admin_label = @Translation("CCC OpenUrl Block"),
 *  context = {
 *    "node" = @ContextDefinition(
 *      "entity:node",
 *      label = @Translation("Current Node")
 *    )
 *  }
 * )
 */
class OpenUrlBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * Constructs a new RightsLinkBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param Token $token
   *   The token service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Token $token) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->token = $token;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('token')
    );
  }

  /**
   * @inheritdoc
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();

    $form['openurl_link_display'] = [
      '#type' => 'fieldset',
      '#tree' => TRUE,
      '#title' => $this->t('RightsLink display options'),
      '#descsription' => $this->t('Configure the display of the RightsLink.'),
    ];

    $form['openurl_link_display']['style'] = [
      '#type' => 'select', 
      '#title' => $this->t('RightsLink style'),
      '#description' => $this->t('Select the style of the RightsLink button.'),
      '#options' => [
        'button' => $this->t('Image button'),
        'icon' => $this->t('Text link with icon'),
        'link' => $this->t('Text link'),
      ],
      '#default_value' => !empty($config['openurl_link_display']['style']) ? $config['openurl_link_display']['style'] : 'icon',
    ];

    $form['openurl_link_display']['link_text'] = [
      '#type' => 'textfield', 
      '#title' => $this->t('Link text'),
      '#description' => $this->t('Text to be displayed. If using the "button" style, this text will be used for screen readers.'),
      '#default_value' => !empty($config['openurl_link_display']['link_text']) ? $config['openurl_link_display']['link_text'] : $this->t('Get permissions'),
    ];

    $form['openurl_link_display']['link_classes'] = [
      '#type' => 'textfield', 
      '#title' => $this->t('Link classes'),
      '#description' => $this->t('Additional classes to be added to the link. You may enter more than one, separated by a space.'),
      '#default_value' => !empty($config['openurl_link_display']['link_classes']) ? $config['openurl_link_display']['link_classes'] : '',
    ];

    $form['openurl_link_fields'] = [
      '#type' => 'fieldset',
      '#tree' => TRUE,
      '#title' => $this->t('Data for rights link.'),
      '#description' => $this->t('All fields support token values; see token information below.'),
    ];

    $form['openurl_link_fields']['issn'] = [
      '#type' => 'textfield',
      '#title' => $this->t('ISSN or ISBN'),
      '#description' => $this->t('The ISSN or ISBN of the content to be licensed for republication.'),
      '#default_value' => !empty($config['openurl_link_fields']['issn']) ? $config['openurl_link_fields']['issn'] : '',
      '#required' => TRUE,
    ];

    $form['openurl_link_fields']['contentid'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Content ID'),
      '#description' => $this->t('The unique identifier for the content.'),
      '#default_value' => !empty($config['openurl_link_fields']['contentid']) ? $config['openurl_link_fields']['contentid'] : '',
    ];

    $form['openurl_link_fields']['publisherName'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Publisher Name'),
      '#description' => $this->t('The publisher or company name.'),
      '#default_value' => !empty($config['openurl_link_fields']['publisherName']) ? $config['openurl_link_fields']['publisherName'] : '',
    ];

    $form['extra_openurl_link_fields'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Extra OpenURL Fields'),
      '#description' => $this->t('Additional query parameters may be provided. <br>Each parameter should be added in the format of key|value. Multiple parameters should be separated by a line break. For example:<pre>credit|[node:credit]<br>vol|[node:volume]<br>issue|[node:issue]</pre>'),
      '#default_value' => !empty($config['extra_openurl_link_fields']) ? $config['extra_openurl_link_fields'] : '',
    ];

    $form['token_help'] = [
      '#theme' => 'token_tree_link',
      '#token_types' => ['node'],
      '#prefix' => '<strong>Token information:</strong><br>'
    ];

    return $form;
  }

  /**
   * @inheritdoc
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    $fields = ['openurl_link_display', 'openurl_link_fields', 'extra_openurl_link_fields'];
    foreach ($fields as $field_name) {
      if (isset($values[$field_name])) {
        $this->configuration[$field_name] = $values[$field_name];
      }
    }
    if (!empty($this->configuration['extra_openurl_link_fields'])) {
      $extra_fields_array = explode("\n", $this->configuration['extra_openurl_link_fields']);
      $extra_fields_array = array_filter(array_map('trim', $extra_fields_array));
      foreach ($extra_fields_array as $metadata) {
        list($key, $value) = explode('|', $metadata);
        if (!empty($key) && !empty($value) && !isset($this->configuration['openurl_link_fields'][$key])) {
          $this->configuration['openurl_link_fields'][$key] = $value;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = $this->getContextValue('node');
    $token_data = ['node' => $node];
    $token_options = ['clear' => TRUE];

    $build = [
      '#cache' => [
        'tags' => Cache::mergeTags($this->getCacheTags(), $node->getCacheTags()),
        'contexts' => $node->getCacheContexts(),
        'max-age' => $node->getCacheMaxAge(),
      ],
    ];

    // Loop through rights link fields and replace tokens with node data.
    $config = $this->getConfiguration();
    $q = [];
    $allowed_tags = '<abbr><b><cite><code><em><i><img><span><strong><sub><sup><wbr>';
    if (!empty($config['openurl_link_fields'])) {
      foreach ($config['openurl_link_fields'] as $key => $value) {
        if (!empty($value)) {
          // Special case: 'publisherName' should be WT.mc.id, 
          // but keys with periods in them is not allowed.
          if ($key == 'publisherName') {
            $key = 'WT.mc.id';
          }

          $replaced_value = $this->token->replace($value, $token_data, $token_options);
          $q[$key] = trim(strip_tags($replaced_value, $allowed_tags));
        }
      }
    }

    // If issn is empty, don't show a permissions link.
    if (empty($q['issn'])) {
      return $build;
    }

    // Build permissions link.
    $build['ccc_permissions_link'] = [
      '#type' => 'ccc_permissions_link',
      '#title' => !empty($config['openurl_link_display']['link_text']) ? $config['openurl_link_display']['link_text'] : $this->t('Get permissions'),
      '#url' => Url::fromUri('http://www.copyright.com/openurl.do', ['query' => $q]),
      '#link_style' => isset($config['openurl_link_display']['style']) ? $config['openurl_link_display']['style'] : 'icon',
    ];
    if (!empty($config['openurl_link_display']['link_classes'])) {
      $build['ccc_permissions_link']['#options']['attributes']['class'] = explode(' ', $config['openurl_link_display']['link_classes']);
    }

    return $build;
  }

}
