<?php

namespace Drupal\jsonp_sparql\Plugin\Block;

use Drupal\Component\Utility\Xss;
use Drupal\Component\Uuid\Php;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Provides a 'SparqlBlock' block.
 *
 * @Block(
 *  id = "sparql_block",
 *  admin_label = @Translation("Sparql block"),
 * )
 */
class SparqlBlock extends BlockBase {

  private $computedValue = NULL;

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['general_settings'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('General settings'),
      '#description' => $this->t(''),
      '#default_value' => isset($this->configuration['general_settings']) ? $this->configuration['general_settings'] : '',
      '#weight' => '0',
    );
    $form['general_settings']['type_of_input'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Type of input'),
      '#description' => $this->t('What type of input you will use for the value replacements.'),
      '#options' => array('field' => $this->t('field'), 'token' => $this->t('token')),
      '#default_value' => isset($this->configuration['type_of_input']) ? $this->configuration['type_of_input'] : 'field',
      '#weight' => '0',
    );
    $form['general_settings']['field'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Field'),
      '#description' => $this->t(''),
      '#default_value' => isset($this->configuration['field']) ? $this->configuration['field'] : '',
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '0',
      '#states' => array(
        'visible' => array(
          ':input[name="settings[general_settings][type_of_input]"]' => array('value' => 'field'),
        ),
      ),
    );
    $form['general_settings']['token'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Token'),
      '#description' => $this->t(''),
      '#default_value' => isset($this->configuration['token']) ? $this->configuration['token'] : '',
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '0',
      '#states' => array(
        'visible' => array(
          ':input[name="settings[general_settings][type_of_input]"]' => array('value' => 'token'),
        ),
      ),
    );
    $form['general_settings']['token_tree'] = [
      '#type' => 'markup',
      '#theme' => 'token_tree_link',
      '#token_types' => [
        'user',
        'node',
        'taxonomy_term',
      ],
      '#states' => array(
        'visible' => array(
          ':input[name="settings[general_settings][type_of_input]"]' => array('value' => 'token'),
        ),
      ),
    ];
    $form['endpoint'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Endpoint'),
      '#description' => $this->t(''),
      '#default_value' => isset($this->configuration['endpoint']) ? $this->configuration['endpoint'] : '',
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '0',
    );
    $form['sparql_query'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('SPARQL query'),
      '#description' => $this->t('SPARQL query to use.'),
      '#default_value' => isset($this->configuration['sparql_query']) ? $this->configuration['sparql_query'] : '',
      '#weight' => '0',
    );
    $form['empty_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Empty message'),
      '#description' => $this->t('Message to display when there were no results'),
      '#default_value' => isset($this->configuration['empty_message']) ? $this->configuration['empty_message'] : '',
    ];
    $form['error_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Error message'),
      '#description' => $this->t('Message to display when there was an error'),
      '#default_value' => isset($this->configuration['error_message']) ? $this->configuration['error_message'] : '',
    ];
    $form['data_template'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Data template'),
      '#description' => $this->t('How to parse and display the data'),
      '#default_value' => isset($this->configuration['data_template']) ? $this->configuration['data_template'] : '',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $settings = $form_state->getValue('general_settings');
    foreach ($settings as $key => $setting) {
      $this->configuration[$key] = $setting;
    }
    $other_keys = [
      'sparql_query',
      'endpoint',
      'empty_message',
      'error_message',
      'data_template',
    ];
    foreach ($other_keys as $key) {
      $this->configuration[$key] = $form_state->getValue($key);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $build['#cache'] = [
      'max-age' => 0,
      'contexts' => ['url'],
    ];
    if (!$this->getComputedValue()) {
      return $build;
    }
    $p = new Php();
    $id = $p->generate();
    $build['#markup'] = '<div class="wrapper"></div>';
    $build['#attributes'] = [
      'data-id' => $id,
      'data-is-sparql-block' => 1,
    ];
    $build['#cache'] = [
      'max-age' => 0,
      'contexts' => ['url'],
    ];
    $build['#attached'] = [
      'library' => [
        'jsonp_sparql/jsonp_sparql',
      ],
      'drupalSettings' => [
        'jsonp_sparql' => [
          $id => $this->createJsSettings(),
        ],
      ],
    ];
    return $build;
  }

  /**
   * Computes a value for use in the query.
   *
   * @return bool|string
   *   The correct string, if possible. Otherwise FALSE.
   */
  private function getComputedValue() {
    try {
      if ($this->computedValue != NULL) {
        return $this->computedValue;
      }
      $this->computedValue = FALSE;
      $request = \Drupal::request();
      $params = $request->attributes->getIterator();
      $entity = NULL;
      foreach ($params as $param) {
        // Is this an entity in some way.
        if ($param instanceof FieldableEntityInterface) {
          $entity = $param;
          break;
        }
      }
      // See if we are using token value of field value.
      if ($this->configuration['type_of_input'] == 'field') {
        if (!$entity) {
          return FALSE;
        }
        $field_value = $entity->get($this->configuration['field'])
          ->getString();
        // This should be an array at this point, right?
        if (empty($field_value)) {
          return FALSE;
        }
        $this->computedValue = $field_value;
        return $this->computedValue;
      }
      // Try to do token replacement.
      /** @var \Drupal\Core\Utility\Token $token_service */
      $token_service = \Drupal::service('token');
      $replacements = [];
      if ($entity) {
        $replacements[$entity->getEntityTypeId()] = $entity;
      }
      $token_config = $this->configuration['token'];
      $value = $token_service->replace($token_config, $replacements);
      // If no replacement was done, assume this went badly.
      if ($value == $token_config) {
        return FALSE;
      }
      $this->computedValue = $value;
      return $this->computedValue;
    }
    catch (\Exception $e) {
      // Something went wrong. We should probably notify the user in some way.
      /** @var LoggerChannelFactoryInterface $logger */
      $logger = \Drupal::service('logger.factory');
      $logger->get('jsonp_sparql')
        ->error('A JSONP SPARQL block is configured incorrectly. Please visit the settings for the block to correct this');
      watchdog_exception('jsonp_sparql', $e);
      $this->computedValue = FALSE;
      return FALSE;
    }
  }

  /**
   * Creates JS settings so JS can do something. With settings.
   *
   * @return array
   */
  private function createJsSettings() {
    return [
      'endpoint' => Xss::filter($this->configuration['endpoint']),
      'sparql_query' => $this->configuration['sparql_query'],
      'value' => Xss::filter($this->getComputedValue()),
      'empty_message' => Xss::filterAdmin($this->configuration['empty_message']),
      'error_message' => Xss::filterAdmin($this->configuration['error_message']),
      'data_template' => Xss::filterAdmin($this->configuration['data_template']),
    ];
  }

}
