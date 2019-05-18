<?php

namespace Drupal\onepass;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\Element;
use Drupal\Component\Utility\Html;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provide OnePass service for handle integration.
 */
class OnepassService implements OnepassServiceInterface {

  /**
   * The onepass.settings config object.
   *
   * @var \Drupal\Core\Config\Config;
   */
  protected $config;

  /**
   * The Onepass node storage.
   *
   * @var \Drupal\Onepass\OnepassNodeStorageInterface
   */
  protected $onepassNodeStorage;

  /**
   * OnePass shortcode.
   */
  protected $shortcode = '[1pass]';

  /**
   * OnePass field name.
   */
  protected $fieldName = 'onepass_button';

  /**
   * Entities marked for trim.
   */
  protected $trimEntities = array();

  /**
   * Currently active request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Constructs OnepassService object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   Configuration object factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   */
  public function __construct(ConfigFactoryInterface $config, EntityTypeManagerInterface $entity_type_manager, RequestStack $request_stack) {
    $this->config = $config->getEditable('onepass.settings');
    $this->onepassNodeStorage = $entity_type_manager->getStorage('onepass_node');
    $this->request = $request_stack->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldName() {
    return $this->fieldName;
  }

  /**
   * {@inheritdoc}
   */
  public function getShortCode() {
    return $this->shortcode;
  }

  /**
   * {@inheritdoc}
   */
  public function getShortCodeReplacement($entity) {

    $secret_key = $this->config->get('secret_key');
    $publishable_key = $this->config->get('publishable_key');

    if (!$secret_key || !$publishable_key) {
      return array();
    }

    return array(
      '#theme' => 'onepass_shortcode_placeholder',
      '#url' => $entity->toUrl('canonical', array('absolute' => TRUE)),
      '#title' => urlencode($entity->label()),
      '#unique_identifier' => $this->getShortCodeReplacementUniqueId($entity->id()),
      '#ts' => REQUEST_TIME,
      '#publisher_id' => $entity->getOwnerId(),
      '#publishable_key' => $publishable_key,
      '#hash' => $this->buildHash($entity->id(), REQUEST_TIME),
      '#author_name' => $entity->getOwner()->getUsername(),
      '#host' => rtrim($this->config->get('host'), '/'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getShortCodeReplacementUniqueId($id) {
    return 'tag:' . parse_url(
      Url::fromRoute('<front>', array(), array('absolute' => TRUE))->toString(),
      PHP_URL_HOST
    ) . ',' . date('Y', REQUEST_TIME) . ':' . $id;
  }

  /**
   * {@inheritdoc}
   */
  public function processingNeeded($entity, $view_mode) {
    return $this->paywallEnabled() &&
           $this->relationExists($entity) &&
           node_is_page($entity) &&
           $view_mode === 'full';
  }

  /**
   * {@inheritdoc}
   */
  public function bundleIntegrationEnabled($bundle) {
    $allowed_bundles = $this->config->get('allowed_types');
    return isset($allowed_bundles[$bundle]) ? 1 : 0;
  }

  /**
   * {@inheritdoc}
   */
  public function manageBundleIntegration($bundle, $action) {
    $allowed_bundles = $this->config->get('allowed_types');

    if ($action) {
      $allowed_bundles[$bundle] = 1;
    }
    elseif (isset($allowed_bundles[$bundle])) {
      unset($allowed_bundles[$bundle]);
    }

    $this->config->set('allowed_types', $allowed_bundles)->save();
  }

  /**
   * {@inheritdoc}
   */
  public function relationExists($entity) {
    return $entity->id() && $this->onepassNodeStorage->loadByNid($entity->id()) ? 1 : 0;
  }

  /**
   * {@inheritdoc}
   */
  public function manageRelation($entity, $action) {
    if ($action) {
      $this->onepassNodeStorage->saveRelation($entity->id());
    }
    else {
      $this->onepassNodeStorage->deleteRelation($entity->id());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function paywallEnabled() {
    return $this->config->get('paywall') ? 1 : 0;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareDisplay(&$build) {

    $hide_fields = FALSE;
    $fields_names = Element::children($build, TRUE);
    foreach ($fields_names as $field_name) {

      if ($field_name === $this->getFieldName() || $this->trimByShortCode($build[$field_name])) {
        $hide_fields = TRUE;
      }
      elseif ($hide_fields) {
        $build[$field_name]['#access'] = FALSE;
      }
    }

    if ($hide_fields) {
      $build['onepass_prefix'] = array(
        '#markup' => '<div class="entry-content">',
        '#weight' => -99999,
      );
      $build['onepass_suffix'] = array(
        '#markup' => '</div>',
        '#weight' => 99999,
      );
      Element::children($build, TRUE);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function removeShortCode(&$build) {
    $fields_names = Element::children($build, TRUE);

    foreach ($fields_names as $field_name) {
      $deltas = Element::children($build[$field_name]);

      foreach ($deltas as $delta) {
        if ($value =& $this->getItemValue($build[$field_name][$delta])) {

          if (strpos($value, $this->getShortCode()) !== FALSE) {
            $value = str_replace($this->getShortCode(), '', $value);
          }
        }
      }
    }
  }

  /**
   * Find and trim by shortcode if it appears.
   *
   * @param array $field
   *   Built field.
   *
   * @return bool
   *   Result of action.
   */
  private function trimByShortCode(array &$field) {

    $deltas = Element::children($field);
    foreach ($deltas as $delta) {

      if ($value =& $this->getItemValue($field[$delta])) {
        if (strpos($value, $this->getShortCode()) !== FALSE) {
          $value = explode($this->getShortCode(), $value);
          $value = reset($value);
          $value = Html::normalize(trim($value));

          return TRUE;
        }

        unset($value);
      }
    }

    return FALSE;
  }

  /**
   * Return reference to item value.
   *
   * @param array $item
   *   Field item definition.
   *
   * @return mixed
   *   Reference to item value or NULL otherwise.
   */
  private function &getItemValue(array &$item) {
    $value = NULL;

    if (isset($item['#context']['value'])) {
      $value =& $item['#context']['value'];
    }
    elseif (isset($item['#text'])) {
      $value =& $item['#text'];
    }
    elseif (isset($item['#markup'])) {
      $value =& $item['#markup'];
    }

    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHash($unique_identifier, $ts) {
    $secret_key = $this->config->get('secret_key');
    $publishable_key = $this->config->get('publishable_key');
    $to_hash = compact('unique_identifier', 'ts', 'publishable_key');
    ksort($to_hash);
    $string = http_build_query($to_hash);
    return hash_hmac('sha1', $string, $secret_key);
  }

  /**
   * {@inheritdoc}
   */
  public function isRequestValid() {
    $hash = $this->request->server->get('HTTP_X_1PASS_SIGNATURE');
    $ts = $this->request->server->get('HTTP_X_1PASS_TIMESTAMP');
    $url = 'http' . ($this->request->server->get('SERVER_PORT') == 443 ? 's://' : '://') .
      $this->request->server->get('HTTP_HOST') . parse_url($this->request->getRequestUri(), PHP_URL_PATH);
    $url = rtrim($url, '/') . '/';
    return $hash === $this->buildHash($url, $ts);
  }

  /**
   * {@inheritdoc}
   */
  public function formatDate($time) {
    return date('Y-m-d\TH:i:s\Z', $time);
  }

  /**
   * Return identifier for requested entity trim.
   *
   * @param object $entity
   *   Entity object.
   *
   * @return string
   *   Entity trim identifier.
   */
  private function buildTrimId($entity) {
    return $entity->getEntityTypeId() . ':' . $entity->id();
  }

  /**
   * {@inheritdoc}
   */
  public function markForTrim($entity) {
    $this->trimEntities[$this->buildTrimId($entity)] = TRUE;
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function trimNeeded($entity) {
    return isset($this->trimEntities[$this->buildTrimId($entity)]);
  }

  /**
   * {@inheritdoc}
   */
  public function cleanupTrimMark($entity) {
    if ($this->trimNeeded($entity)) {
      unset($this->trimEntities[$this->buildTrimId($entity)]);
    }
  }

}
