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
 * Provides a 'RightsLinkBlock' block.
 *
 * @Block(
 *  id = "ccc_rightslink_block",
 *  admin_label = @Translation("CCC RightsLink Block"),
 *  context = {
 *    "node" = @ContextDefinition(
 *      "entity:node",
 *      label = @Translation("Current Node")
 *    )
 *  }
 * )
 */
class RightsLinkBlock extends BlockBase implements ContainerFactoryPluginInterface {

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

    $form['rights_link_display'] = [
      '#type' => 'fieldset',
      '#tree' => TRUE,
      '#title' => $this->t('RightsLink display options'),
      '#descsription' => $this->t('Configure the display of the RightsLink.'),
    ];

    $form['rights_link_display']['style'] = [
      '#type' => 'select', 
      '#title' => $this->t('RightsLink style'),
      '#description' => $this->t('Select the style of the RightsLink button.'),
      '#options' => [
        'button' => $this->t('Image button'),
        'icon' => $this->t('Text link with icon'),
        'link' => $this->t('Text link'),
      ],
      '#default_value' => !empty($config['rights_link_display']['style']) ? $config['rights_link_display']['style'] : 'icon',
    ];

    $form['rights_link_display']['link_text'] = [
      '#type' => 'textfield', 
      '#title' => $this->t('Link text'),
      '#description' => $this->t('Text to be displayed. If using the "button" style, this text will be used for screen readers.'),
      '#default_value' => !empty($config['rights_link_display']['link_text']) ? $config['rights_link_display']['link_text'] : $this->t('Get permissions'),
    ];

    $form['rights_link_display']['link_classes'] = [
      '#type' => 'textfield', 
      '#title' => $this->t('Link classes'),
      '#description' => $this->t('Additional classes to be added to the link. You may enter more than one, separated by a space.'),
      '#default_value' => !empty($config['rights_link_display']['link_classes']) ? $config['rights_link_display']['link_classes'] : '',
    ];

    $form['rights_link_fields'] = [
      '#type' => 'fieldset',
      '#tree' => TRUE,
      '#title' => $this->t('Data for rights link.'),
      '#description' => $this->t('Unless otherwise specified, all fields are needed for the rights link. Some fields are content type dependent.<br>All fields support token values; see token information below.'),
    ];

    $form['rights_link_fields']['contentid'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Content ID'),
      '#description' => $this->t('The unique identifier for the content.'),
      '#default_value' => !empty($config['rights_link_fields']['contentid']) ? $config['rights_link_fields']['contentid'] : '',
      '#required' => TRUE,
    ];

    $form['rights_link_fields']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#description' => $this->t('The title of the article (or other content) to be licensed for republication.'),
      '#default_value' => !empty($config['rights_link_fields']['title']) ? $config['rights_link_fields']['title'] : '',
    ];

    $form['rights_link_fields']['publisherName'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Publisher Name'),
      '#description' => $this->t('The publisher name provided to you by the RightsLink technical representative.'),
      '#default_value' => !empty($config['rights_link_fields']['publisherName']) ? $config['rights_link_fields']['publisherName'] : '',
    ];

    $form['rights_link_fields']['publication'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Publication'),
      '#description' => $this->t('The publication name you provide to the RightsLink technical representative.'),
      '#default_value' => !empty($config['rights_link_fields']['publication']) ? $config['rights_link_fields']['publication'] : '',
    ];

    $form['rights_link_fields']['publicationDate'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Publication Date'),
      '#description' => $this->t('Date of creation or publication, acceptable formats: <ol><li>mm/dd/yyyy = 09/08/2003</li><li>yyyy-mm-dd = 2003-09-08</li><li>mmm d yyyy = sep 9 2003</li><li>mmm d, yyyy = sep 9, 2003</li><li>yyyy = 2003</li><li>&lt;month name&gt; yyyy = August 2003</li><li>dd MMMMMM yyyy = 2 MARCH 2011</li></ol>'),
      '#default_value' => !empty($config['rights_link_fields']['publicationDate']) ? $config['rights_link_fields']['publicationDate'] : '',
    ];

    $form['rights_link_fields']['author'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Author(s)'),
      '#description' => $this->t('The name of the author(s) of the content. Please provide up to three author names followed by "et al" where more than three exist.'),
      '#default_value' => !empty($config['rights_link_fields']['author']) ? $config['rights_link_fields']['author'] : '',
    ];

    $form['rights_link_fields']['bookTitle'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Book Title'),
      '#description' => $this->t('The title of the book to be licensed for republication. Only required for books / chapters.'),
      '#default_value' => !empty($config['rights_link_fields']['bookTitle']) ? $config['rights_link_fields']['bookTitle'] : '',
    ];

    $form['rights_link_fields']['ISBN'] = [
      '#type' => 'textfield',
      '#title' => $this->t('ISBN'),
      '#description' => $this->t('The individual number given to every book that is published. May be passed as ISBN, PISBN, or EISBN.'),
      '#default_value' => !empty($config['rights_link_fields']['ISBN']) ? $config['rights_link_fields']['ISBN'] : '',
    ];

    $form['extra_rights_link_fields'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Extra RightsLink Fields'),
      '#description' => $this->t('Additional metadata may be provided to best meet publication needs (e.g. credit, volume number, issue number). <br>Each piece of metadata should be added in the format of key|value. Multiple metadata items should be separated by a line break. For example:<pre>credit|[node:credit]<br>vol|[node:volume]<br>issue|[node:issue]</pre>'),
      '#default_value' => !empty($config['extra_rights_link_fields']) ? $config['extra_rights_link_fields'] : '',
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
    $fields = ['rights_link_display', 'rights_link_fields', 'extra_rights_link_fields'];
    foreach ($fields as $field_name) {
      if (isset($values[$field_name])) {
        $this->configuration[$field_name] = $values[$field_name];
      }
    }
    if (!empty($this->configuration['extra_rights_link_fields'])) {
      $extra_fields_array = explode("\n", $this->configuration['extra_rights_link_fields']);
      $extra_fields_array = array_filter(array_map('trim', $extra_fields_array));
      foreach ($extra_fields_array as $metadata) {
        list($key, $value) = explode('|', $metadata);
        if (!empty($key) && !empty($value) && !isset($this->configuration['rights_link_fields'][$key])) {
          $this->configuration['rights_link_fields'][$key] = $value;
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
    if (!empty($config['rights_link_fields'])) {
      foreach ($config['rights_link_fields'] as $key => $value) {
        if (!empty($value)) {
          $replaced_value = $this->token->replace($value, $token_data, $token_options);
          $q[$key] = trim(strip_tags($replaced_value, $allowed_tags));
        }
      }
    }

    // If contentid is empty, don't show a permissions link.
    if (empty($q['contentid'])) {
      return $build;
    }

    // Build permissions link.
    $build['ccc_permissions_link'] = [
      '#type' => 'ccc_permissions_link',
      '#title' => !empty($config['rights_link_display']['link_text']) ? $config['rights_link_display']['link_text'] : $this->t('Get permissions'),
      '#url' => Url::fromUri('https://s100.copyright.com/AppDispatchServlet', ['query' => $q]),
      '#link_style' => isset($config['rights_link_display']['style']) ? $config['rights_link_display']['style'] : 'icon',
    ];
    if (!empty($config['rights_link_display']['link_classes'])) {
      $build['ccc_permissions_link']['#options']['attributes']['class'] = explode(' ', $config['rights_link_display']['link_classes']);
    }

    return $build;
  }

}
