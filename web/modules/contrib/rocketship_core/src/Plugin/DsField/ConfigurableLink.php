<?php

namespace Drupal\rocketship_core\Plugin\DsField;

use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ds\Plugin\DsField\DsFieldBase;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Utility\Token;
use Drupal\Core\Path\AliasStorageInterface;

/**
 * Outputs a link that is configurable.
 *
 * @DsField(
 *   id = "configurable_link",
 *   title = @Translation("Configurable link"),
 *   entity_type =  {"node","taxonomy_term"},
 *   provider = "rocketship_core"
 * )
 */
class ConfigurableLink extends DsFieldBase {

  /**
   * The Token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * Provides a service for CRUD operations on path aliases.
   *
   * @var \Drupal\Core\Path\AliasStorageInterface
   */
  protected $aliasStorage;

  /**
   * Constructs a Display Suite field plugin.
   */
  public function __construct($configuration, $plugin_id, $plugin_definition, Token $token_service, AliasStorageInterface $alias_storage) {
    $this->token = $token_service;
    $this->aliasStorage = $alias_storage;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('token'),
      $container->get('path.alias_storage')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'link' => '',
      'link_text' => 'Back to overview',
      'link_class' => '',
      'query_string' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary($settings) {
    $config = $this->getConfiguration();

    $summary = [];
    $summary[] = 'Url: ' . $config['link'];
    $summary[] = 'Link text: ' . $config['link_text'];
    $summary[] = 'Class: ' . $config['link_class'];
    $summary[] = 'Query string: ' . isset($config['query_string']) ? $config['query_string'] : '';

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm($form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();

    $form['link'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'node',
      '#process_default_value' => FALSE,
      '#title' => $this->t('URL'),
      '#default_value' => $config['link'],
      '#description' => $this->t('Start typing the title of a piece of content to select it. You can also enter an internal path such as %add-node or an external URL such as %url. Enter %front to link to the front page. Token input is available, if token replacement will result with empty text link will not be rendered.', [
        '%front' => '<front>',
        '%add-node' => '/node/add',
        '%url' => 'http://example.com',
      ]),
      '#required' => TRUE,
      '#element_validate' => [[get_called_class(), 'validateUriElement']],
    ];
    $form['link_text'] = [
      '#title' => t('Link text'),
      '#description' => t('Text for the link.'),
      '#type' => 'textfield',
      '#default_value' => $config['link_text'],
      '#required' => TRUE,
    ];
    $form['link_class'] = [
      '#title' => t('Link class'),
      '#description' => t('Classes separated with a comma.'),
      '#type' => 'textfield',
      '#default_value' => $config['link_class'],
      '#required' => FALSE,
    ];
    $form['query_string'] = [
      '#title' => 'Query String',
      '#description' => 'Add the query string which should be added to the link, eg: title=My Title&subject=[node:title]. Do NOT include the "?"',
      '#type' => 'textfield',
      '#default_value' => isset($config['query_string']) ? $config['query_string'] : NULL,
      '#required' => FALSE,
    ];

    $form['tokens'] = [
      '#theme' => 'token_tree_link',
      '#token_types' => $this->getEntityTypeId() == 'node' ? ['node'] : ['term'],
      '#show_restricted' => FALSE,
      '#dialog' => TRUE,
    ];

    return $form;
  }

  /**
   * Form element validation handler for the URL element of link widget.
   *
   * @param array $element
   *   Element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   * @param array $form
   *   Form.
   *
   * @see \Drupal\link\Plugin\Field\FieldWidget\LinkWidget
   */
  public static function validateUriElement(array $element,
                                            FormStateInterface $form_state,
                                            array $form) {
    $uri = static::getUserEnteredStringAsUri($element['#value']);
    $form_state->setValueForElement($element, $uri);

    // If getUserEnteredStringAsUri() mapped the entered value to a 'internal:'
    // URI , ensure the raw value begins with '/', '?' or '#'.
    // @todo '<front>' is valid input for BC reasons, may be removed by
    //   https://www.drupal.org/node/2421941
    if (parse_url($uri, PHP_URL_SCHEME) === 'internal' && !in_array($element['#value'][0],
        [
          '/',
          '?',
          '#',
        ], TRUE) && substr($element['#value'], 0, 7) !== '<front>') {
      $form_state->setError($element, t('Manually entered paths should start with /, ? or #.'));
      return;
    }
  }

  /**
   * Gets the user-entered string as a URI.
   *
   * @param string $string
   *   String.
   *
   * @return string
   *   The URI.
   *
   * @see \Drupal\link\Plugin\Field\FieldWidget\LinkWidget
   */
  protected static function getUserEnteredStringAsUri($string) {
    // By default, assume the entered string is an URI.
    $uri = trim($string);
    // Detect entity autocomplete string, map to 'entity:' URI.
    $entity_id = EntityAutocomplete::extractEntityIdFromAutocompleteInput($string);
    if ($entity_id !== NULL) {
      // @todo Support entity types other than 'node'. Will be fixed in
      //   https://www.drupal.org/node/2423093.
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
   * {@inheritdoc}
   */
  public function build() {
    // Get the config.
    $config = $this->getConfiguration();

    // Tokenize link title.
    $link_text = $this->token->replace($config['link_text'], [$this->getEntityTypeId() => $this->entity()], ['clear' => TRUE]);

    if (!strlen($link_text)) {
      return FALSE;
    }

    // Try to tokenize link URL.
    $link = $this->token->replace($config['link'], [$this->getEntityTypeId() => $this->entity()], ['clear' => TRUE]);

    // Prepare the url.
    $url = Url::fromUri($link);

    // Extra check for cases when configured URL is a path alias,
    // then we need to get source path like /node/{nid} so it will nicely
    // work with different languages.
    $langcode = $this->entity()->language()->getId();
    $source = $this->aliasStorage->lookupPathSource($url->toString(), $langcode);
    if ($source) {
      $url = Url::fromUri($this::getUserEnteredStringAsUri($source));
    }

    // Check if we have classes available.
    if (!empty($config['link_class'])) {
      $url->setOption('attributes', ['class' => explode(',', $config['link_class'])]);
    }

    // Add the query string if available.
    if (!empty($config['query_string'])) {
      // Get it all parsed and translated.
      $query_array = $this->parseString($config['query_string']);
      $url->setOption('query', $query_array);
    }

    return Link::fromTextAndUrl($this->t($link_text), $url)
      ->toRenderable();
  }

  /**
   * Parses query string in CGI-compliant way.
   *
   * Also translates and tokenizes values.
   *
   * @param string $str
   *   String to parse into an array.
   *
   * @return array
   *   Returned array.
   *
   * @see https://php.net/manual/en/function.parse-str.php#76792
   */
  protected function parseString($str) {
    $arr = [];
    $pairs = explode('&', $str);
    foreach ($pairs as $i) {
      list($name, $value) = explode('=', $i, 2);
      // Translate then tokenize value.
      $value = $this->token->replace(t($value), [$this->getEntityTypeId() => $this->entity()], ['clear' => TRUE]);
      if (isset($arr[$name])) {
        if (is_array($arr[$name])) {
          $arr[$name][] = $value;
        }
        else {
          $arr[$name] = [$arr[$name], $value];
        }
      }
      else {
        $arr[$name] = $value;
      }
    }
    return $arr;
  }

}
