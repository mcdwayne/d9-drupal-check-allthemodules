<?php

namespace Drupal\trumba\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Cache\CacheTagsInvalidator;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;

/**
 * Defines a base block implementation that Trumba blocks plugins will extend.
 */
abstract class TrumbaBlockBase extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The default Trumba Web Name.
   * @var string
   */
  public $defaultTrumbaWebName;

  /**
   * A unique HTML element id to use as the spud id.
   * @var string
   */
  public $spudId;
  /**
   * @var \Drupal\Core\Cache\CacheTagsInvalidator
   */
  public $cacheInvalidator;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CacheTagsInvalidator $cacheInvalidator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->defaultTrumbaWebName = \Drupal::config('trumba.trumbaconfiguration')->get('default_web_name');
    $this->spudId = Html::getUniqueId($this->getBaseId());
    $this->cacheInvalidator = $cacheInvalidator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('cache_tags.invalidator')
    );
  }
  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['trumba_web_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Web Name'),
      '#description' => $this->t('This is the unique identifier for your calendar account on Trumba.'),
      '#default_value' => isset($this->configuration['trumba_web_name']) ? $this->configuration['trumba_web_name'] : $this->defaultTrumbaWebName,
      '#maxlength' => 255,
      '#size' => 64,
      '#weight' => '1',
      '#required' => TRUE,
    ];

    $form['trumba_same_page'] = [
      '#type' => 'radios',
      '#title' => $this->t('Is this block on the same page as the Main Calendar?'),
      '#default_value' => isset($this->configuration['trumba_same_page']) ? $this->configuration['trumba_same_page'] : 0,
      '#options' => array(0 => $this->t('Yes'), 1 => $this->t('No')),
    ];

    $form['trumba_spud_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Calendar URL'),
      '#description' => $this->t('Enter the internal path (e.g.: "/node/1") or the full URL for where this calendar will be placed (e.g.: "https://www.yoursite.com/calendar").'),
      '#default_value' => (isset($this->configuration['trumba_spud_url']) &&
        !empty($this->configuration['trumba_spud_url'])) ?
        $this->convertUriToRelativePathOrUrl($this->configuration['trumba_spud_url']) : '',
      '#maxlength' => 255,
      '#size' => 64,
      '#weight' => '2',
      '#states' => [
        'invisible' =>
          [':input[name="settings[trumba_same_page]"]' => ['value' => 0]],
        'required' =>
          [':input[name="settings[trumba_same_page]"]' => ['value' => 1]],
      ],
    ];

    return $form;
  }


  /**
   * Checks to see if the block should be shown per permissions.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   * @return \Drupal\Core\Access\AccessResult
   */
  protected function blockAccess(AccountInterface $account) {
    // The block is visible to those that have permission to view trumba
    // spud blocks.
    return AccessResult::allowedIfHasPermission($account,'view trumba spud blocks');
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {
    // Ensure that internal url's start with a forward slash.
    $rawUrl = $form_state->getValue('trumba_spud_url');
    $firstChar = substr($rawUrl, 0, 1);
    if (!empty($rawUrl) && !$this->isExternalUri($rawUrl) && $firstChar != '/') {
      $form_state->setErrorByName('trumba_spud_url', $this->t('Internal Url\'s must begin with a forward slash.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->cacheInvalidator->invalidateTags(['trumba:' . $this->spudId]);
    $this->configuration['trumba_web_name'] = $form_state->getValue('trumba_web_name');
    $this->configuration['trumba_same_page'] = $form_state->getValue('trumba_same_page');
    $this->configuration['trumba_spud_url'] = $this->convertInputToUriString($form_state->getValue('trumba_spud_url'));
  }

  /**
   * Convert a saved Uri String to an Absolute path for internal uri's or a full
   *   Url for external urls
   * @param $uri
   * @return \Drupal\Core\GeneratedUrl|string
   */
  public function convertUriToAbsolutePathOrUrl($uri) {
    return $uri ? Url::fromUri($uri)->setAbsolute()->toString() : '';
  }

  /**
   * Convert a saved Uri String to an relative path for internal uri's or a full
   *  Url for external urls
   * @param $uri
   * @return \Drupal\Core\GeneratedUrl|string
   */
  public function convertUriToRelativePathOrUrl($uri) {
    return $uri ? Url::fromUri($uri)->toString() : '';
  }

  /**
   * Convert a full url or internal path string to a system Uri.
   * @param $input
   * @return string
   */
  public function convertInputToUriString($input) {
    $uri = '';
    if ($input) {
      if ($this->isExternalUri($input)) {
        $uri = Url::fromUri($input)->toUriString();
      }
      else {
        $uri = Url::fromUserInput($input)->toUriString();
      }
    }
    return $uri;
  }

  /**
   * Check if given Uri is an external url.
   * @param $uri
   * @return bool
   */
  public function isExternalUri($uri) {
    $parts = parse_url($uri);
    return (!empty($parts['host'])) ? TRUE : FALSE;
  }

}
