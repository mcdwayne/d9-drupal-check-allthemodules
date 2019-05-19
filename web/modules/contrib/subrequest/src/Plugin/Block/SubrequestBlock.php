<?php

/**
 * @file
 * Contains \Drupal\subrequest\Plugin\Block\SubrequestBlock.
 */

namespace Drupal\subrequest\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\subrequest\SubrequestManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a subrequest block.
 *
 * @Block(
 *   id = "subrequest",
 *   admin_label = @Translation("Subrequest"),
 *   category = @Translation("Subrequest")
 * )
 */
class SubrequestBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The subrequest manager.
   *
   * @var \Drupal\subrequest\SubrequestManager
   */
  protected $subrequestManager;

  /**
   * Constructs a new SubrequestBlock instance.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\subrequest\SubrequestManager $subrequest_manager
   *   The subrequest manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, SubrequestManager $subrequest_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->subrequestManager = $subrequest_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('subrequest.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['uri' => '' ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {

    $form['uri'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('URL'),
      '#placeholder' => '/contact',
      // The current field value could have been entered by a different user.
      // However, if it is inaccessible to the current user, do not display it
      // to them.
      '#default_value' => static::getUriAsDisplayableString($this->configuration['uri']),
      '#element_validate' => [[get_called_class(), 'validateUriElement']],
      '#maxlength' => 2048,
      '#required' => TRUE,
      // @todo The user should be able to select an entity type.
      // @see https://www.drupal.org/node/2423093.
      '#target_type' => 'node',

      // Disable autocompletion when the first character is '/', '#' or '?'.
      '#attributes' => ['data-autocomplete-first-character-blacklist' => '/#?'],

      // The link widget is doing its own processing in
      // static::getUriAsDisplayableString().
      '#process_default_value' => FALSE,

      '#description' => $this->t(
        'Start typing the title of a piece of content to select it. You can also enter an internal path such as %add-node. Enter %front to link to the front page.',
        ['%front' => '<front>', '%add-node' => '/node/add']
      ),

    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * @DCG: Optional.
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['uri'] = $form_state->getValue('uri');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    $build = $this->subrequestManager->geResponse(
      $this->configuration['uri']
    );

    return $build ? $build : [];
  }

  /**
   * Form element validation handler for the 'url' element.
   *
   * Disallows saving inaccessible or untrusted URLs.
   *
   * @todo: Cleanup (this has been taken from LinkWiget as is).
   */
  public static function validateUriElement($element, FormStateInterface $form_state, $form) {
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
   *
   * @todo: Cleanup (this has been taken from LinkWiget as is).
   */
  protected static function getUserEnteredStringAsUri($string) {
    // By default, assume the entered string is an URI.
    $uri = $string;

    // Detect entity autocomplete string, map to 'entity:' URI.
    $entity_id = EntityAutocomplete::extractEntityIdFromAutocompleteInput($string);
    if ($entity_id !== NULL) {
      // Support entity types other than 'node'.
      // @see https://www.drupal.org/node/2423093.
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
   *   Human readable string.
   *
   * @see static::getUserEnteredStringAsUri()
   *
   * @todo: Cleanup (this has been taken from LinkWiget as is).
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
      $entity_manager = \Drupal::entityManager();
      if ($entity_manager->getDefinition($entity_type, FALSE) && $entity = \Drupal::entityManager()->getStorage($entity_type)->load($entity_id)) {
        $displayable_string = EntityAutocomplete::getEntityLabels([$entity]);
      }
    }

    return $displayable_string;
  }

}
