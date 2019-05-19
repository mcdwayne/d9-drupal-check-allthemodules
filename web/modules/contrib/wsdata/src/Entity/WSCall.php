<?php

namespace Drupal\wsdata\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheableDependencyInterface;

/**
 * Defines the Web Service Call entity.
 *
 * @ConfigEntityType(
 *   id = "wscall",
 *   label = @Translation("Web Service Call"),
 *   handlers = {
 *     "list_builder" = "Drupal\wsdata\WSCallListBuilder",
 *     "view_builder" = "Drupal\wsdata\WSCallViewBuilder",
 *     "form" = {
 *       "add" = "Drupal\wsdata\Form\WSCallForm",
 *       "edit" = "Drupal\wsdata\Form\WSCallForm",
 *       "delete" = "Drupal\wsdata\Form\WSCallDeleteForm",
 *       "test" = "Drupal\wsdata\Form\WSCallTestForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\wsdata\WSCallHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "wscall",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/wscall/{wscall}",
 *     "add-form" = "/admin/structure/wscall/add",
 *     "edit-form" = "/admin/structure/wscall/{wscall}/edit",
 *     "delete-form" = "/admin/structure/wscall/{wscall}/delete",
 *     "collection" = "/admin/structure/wscall"
 *   }
 * )
 */
class WSCall extends ConfigEntityBase implements WSCallInterface {
  /**
   * The Web Service Call ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Web Service Call label.
   *
   * @var string
   */
  protected $label;

  public $options;

  public $wsserver;
  public $wsdecoder;
  public $wsencoder;

  protected $wsserverInst;
  protected $wsdecoderInst;
  protected $wsencoderInst;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $values, $entity_type) {
    parent::__construct($values, $entity_type);
    $this->wsserverInst = entity_load('wsserver', $this->wsserver);
    // Set the decoder instance.
    $wsdecoderManager = \Drupal::service('plugin.manager.wsdecoder');
    $wspdefs = $wsdecoderManager->getDefinitions();
    if (isset($wspdefs[$this->wsdecoder])) {
      $this->wsdecoderInst = $wsdecoderManager->createInstance($this->wsdecoder);
    }
    // Set the enocder instance.
    $wsencoderManager = \Drupal::service('plugin.manager.wsencoder');
    $wspenfs = $wsencoderManager->getDefinitions();
    if (isset($wspenfs[$this->wsencoder])) {
      $this->wsencoderInst = $wsencoderManager->createInstance($this->wsencoder);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setEndpoint($endpoint) {
    if ($this->wsserverInst) {
      $this->wsserverInst->setEndpoint($endpoint);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getEndpoint() {
    return $this->wsserverInst ? $this->wsserverInst->getEndpoint() : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getLanguagePlugin() {}

  /**
   * {@inheritdoc}
   */
  public function call($method = NULL, $replacements = [], $data = NULL, $options = [], $key = NULL, $tokens = [])  {
    if (!$this->wsserverInst) {
      return FALSE;
    }
    // Build out the Cache ID based on the parameters passed.
    $cid_array = array_merge($options, $this->getOptions(), $replacements, $tokens, array('data' => $data, 'key' => $key));
    $cid = md5(serialize($cid_array));
    if ($cache = \Drupal::cache('wsdata')->get($cid)) {
      $cache_data = $cache->data;
      $this->addData($cache_data);
      return $this->getData($key);
    }
    else {
      $conn = $this->getConnector();
      $options = array_merge($options, $this->getOptions());

      if ($method and !in_array($method, $conn->getMethods())) {
        throw new WSDataInvalidMethodException(sprintf('Invalid method %s on connector type %s', $method, $this->wsserverInst->wsconnector));
      }
      elseif (isset($options['method']) and in_array($options['method'], $conn->getMethods())) {
        $method = $options['method'];
      }
      else {
        $methods = $conn->getMethods();
        $method = reset($methods);
      }

      $context = [
        'replacements' => $replacements,
        'data' => $data,
        'options' => $options,
        'key' => $key,
        'tokens' => $tokens,
      ];
      // Encode the payload data.
      $this->wsencoderInst->encode($data, $replacements, $options['path'], $context);
      // Call the connector.
      $cache_data = $conn->call($options, $method, $replacements, $data, $tokens);

      // Set the cache for this data if there wasn't an error && if the connector support caching.
      if (empty($conn->getError()) && $conn->supportsCaching($method)) {
        $expires = time() + $conn->expires();
        // Fetch the cache tags for this call and the server instance call.
        $cache_tags = array_merge($this->wsserverInst->getCacheTags(), $this->getCacheTags());
        \Drupal::cache('wsdata')->set($cid, $cache_data, $expires, $cache_tags);
      }
      elseif(!empty($conn->getError())) {
        \Drupal::logger('wsdata')->error(t('wsdata %wsdata_name failed with error %code %message',
          array('%wsdata_name' => $this->id, '%code' => $conn->getError()['code'], '%message' => $conn->getError()['message'])));
      }
    }

    if ($cache_data) {
      $this->addData($cache_data, $context);
      return $this->getData($key);
    } else {
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   *
   * Doesn't save the WSCall though.
   */
  public function setOptions($values = []) {
    if (isset($this->wsserverInst->wsconnectorInst)) {
      $this->options[$this->wsserver] = $this->wsserverInst->wsconnectorInst->saveOptions($values);
    }

    $this->needSave = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getReplacements() {
    return $this->wsserverInst ? $this->wsserverInst->wsconnectorInst->getReplacements($this->getOptions()) : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getOptionsForm($wsserver = NULL, $options = []) {
    if (isset($wsserver)) {
      $wsserverInst = entity_load('wsserver', $wsserver);
      return $wsserverInst->wsconnectorInst->getOptionsForm($options);
    }
    if (isset($this->wsserverInst->wsconnectorInst)) {
      return $this->wsserverInst->wsconnectorInst->getOptionsForm($options);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions() {
    return isset($this->options[$this->wsserver]) ? $this->options[$this->wsserver] : array();
  }

  /**
   * {@inheritdoc}
   */
  public function getMethods() {
    return $this->wsserverInst->getMethods();
  }

  /**
   * {@inheritdoc}
   */
  public function addData($data, $context = []) {
    if (!isset($this->wsdecoderInst)) {
      $wsdecoderManager = \Drupal::service('plugin.manager.wsdecoder');
      $this->wsdecoderInst = $wsdecoderManager->createInstance($this->wsdecoder);
    }
    return $this->wsdecoderInst->addData($data, NULL, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function getData($key = NULL) {
    if (isset($this->wsdecoderInst)) {
      return $this->wsdecoderInst->getData($key);
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getConnector() {
    return $this->wsserverInst ? $this->wsserverInst->getConnector() : FALSE;
  }
}
