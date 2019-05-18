<?php

namespace Drupal\elastic_search\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\elastic_search\Entity\ElasticIndex;
use Drupal\elastic_search\ValueObject\BatchDefinition;

/**
 * Class ServerForm.
 *
 * @package Drupal\elastic_search\Form
 */
class ServerForm extends ConfigFormBase {

  /**
   * @var
   */
  private $configuration;

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'elastic_search.server',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'elastic_search_server_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->configuration = $this->config('elastic_search.server');
    $form['scheme'] = [
      '#type'          => 'select',
      '#title'         => $this->t('HTTP protocol'),
      '#description'   => $this->t('The HTTP protocol to use for sending queries.'),
      '#default_value' => $this->configuration->get('scheme'),
      '#options'       => [
        'http'  => 'http',
        'https' => 'https',
      ],
    ];

    $form['host'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Elastic host'),
      '#description'   => $this->t('The host name or IP of your Elastic server, e.g. <code>localhost</code> or <code>www.example.com</code>. WITHOUT http(s)'),
      '#default_value' => $this->configuration->get('host'),
      '#required'      => TRUE,
    ];

    $form['port'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Elastic port'),
      '#description'   => $this->t('Http default is 9200. For https default is usually 9243'),
      '#default_value' => $this->configuration->get('port'),
      '#required'      => TRUE,
    ];

    $form['kibana'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Kibana host'),
      '#description'   => $this->t('The host name or IP of your Kibana server, e.g. <code>localhost</code> or <code>www.example.com</code>.'),
      '#default_value' => $this->configuration->get('kibana'),
    ];

    $form['index_prefix'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Index prefix'),
      '#description'   => $this->t('Anything entered in this field will be directly prefixed to the index names. The prefix is added dynamically, therefore if you change the prefix you should be sure to delete your existing indices first'),
      '#default_value' => $this->configuration->get('index_prefix'),
    ];

    $form['auth'] = [
      '#tree'        => TRUE,
      '#type'        => 'details',
      '#title'       => $this->t('Elastic authentication'),
      '#description' => $this->t('If your Elastic server is protected, enter the login data here.'),
      '#collapsible' => TRUE,
      '#collapsed'   => TRUE,
    ];

    $form['auth']['username'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Username'),
      '#default_value' => $this->configuration->get('auth.username'),
    ];
    $form['auth']['password'] = [
      '#type'        => 'textfield',
      '#title'       => $this->t('Password'),
      '#description' => $this->t('If this field is left blank and the username is filled out, the current password will not be changed.'),
    ];

    $form['advanced'] = [
      '#tree'        => TRUE,
      '#type'        => 'details',
      '#title'       => $this->t('Advanced Configuration'),
      '#collapsible' => TRUE,
      '#collapsed'   => TRUE,
    ];

    $form['advanced']['pause'] = [
      '#type'          => 'number',
      '#title'         => $this->t('Pause duration'),
      '#default_value' => $this->configuration->get('advanced.pause'),
      '#description'   => $this->t('If this is set then at the end of any batch operation that contacts the remote server the function will sleep for this time. This may help relieve memory pressure on constrained elastic resources'),
    ];

    $form['advanced']['batch_size'] = [
      '#type'          => 'number',
      '#title'         => $this->t('Batch Size'),
      '#default_value' => $this->configuration->get('advanced.batch_size'),
      '#description'   => $this->t('How many documents to index at once on bulk and queue operations. Elastic search likes bulk updates around 5-15mb https://www.elastic.co/guide/en/elasticsearch/guide/current/indexing-performance.html#_using_and_sizing_bulk_requests'),
    ];

    $form['advanced']['index_batch_size'] = [
      '#type'          => 'number',
      '#title'         => $this->t('Index Batch Size'),
      '#default_value' => $this->configuration->get('advanced.index_batch_size'),
      '#description'   => $this->t('How many indices to CRUD per batch, low is usually better'),
    ];

    $form['advanced']['queue_update'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Queue updated documents'),
      '#default_value' => $this->configuration->get('advanced.queue_update'),
      '#description'   => $this->t('If true queue all updated documents for indexing instead of pushing at save time. Defaults to true. Because of the nature of elasticsearch and how referenced entities are dealt with a single save has the potential to update a large number of nodes at once. For example updating a taxonomy term will update all documents that reference this term. To prevent timeouts it is recommended to leave this setting on'),
    ];

    $form['advanced']['queue_insert'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Queue new documents'),
      '#default_value' => $this->configuration->get('advanced.queue_insert'),
      '#description'   => $this->t('If true queue node insert actions for indexing. Defaults to false. As it is assumed that a new document cannot have an existing backreference in a document this action always results in only a single object being pushed to elastic search and therefore is usually safe to do at save time. However if your documents contain a large number of references and/or a deep recursion depth it may be prudent to active this'),
    ];

    $form['advanced']['queue_delete'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Queue delete documents'),
      '#default_value' => $this->configuration->get('advanced.queue_delete'),
      '#description'   => $this->t('If true queue all deleted documents for removal instead of actioning at local delete time. Defaults to true. Because of the nature of elasticsearch and how referenced entities are dealt with a single save has the potential to update a large number of nodes at once. For example updating a taxonomy term will update all documents that reference this term. To prevent timeouts it is recommended to leave this setting on'),
    ];

    $form['advanced']['developer'] = [
      '#tree'        => TRUE,
      '#type'        => 'details',
      '#title'       => $this->t('Developer Settings'),
      '#collapsible' => TRUE,
      '#collapsed'   => TRUE,
    ];

    $form['advanced']['developer']['active'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Developer/Debug mode'),
      '#description'   => $this->t('Set developer/debug mode to true, this injects a logger into the elastic search library, should not be used in prod due to performance concerns'),
      '#default_value' => $this->configuration->get('advanced.developer.active'),
    ];

    $form['advanced']['developer']['logging_channel'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('logging channel'),
      '#description'   => $this->t('The name of the logging channel to use for developer output'),
      '#default_value' => $this->configuration->get('advanced.developer.logging_channel'),
    ];

    $form['advanced']['validate'] = [
      '#tree'        => TRUE,
      '#type'        => 'details',
      '#title'       => $this->t('Connection Validation'),
      '#collapsible' => TRUE,
      '#collapsed'   => TRUE,
    ];

    $form['advanced']['validate']['active'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Validate connection'),
      '#description'   => $this->t('If this is true ElasticConnectionFactory will try to connect to the index before returning the connection'),
      '#default_value' => $this->configuration->get('advanced.validate.active'),
    ];

    $form['advanced']['validate']['die_hard'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Hard exceptions'),
      '#description'   => $this->t('If this is true ElasticConnectionFactory will not gracefully fail if a connection cannot be validated'),
      '#default_value' => $this->configuration->get('advanced.validate.die_hard'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $values = &$form_state->getValues();
    if (isset($values['port']) &&
        (!is_numeric($values['port']) || $values['port'] < 0 ||
         $values['port'] > 65535)
    ) {
      $form_state->setError($form['port'],
                            $this->t('The port has to be an integer between 0 and 65535.'));
    }
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Config\ConfigValueException
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $config = $this->config('elastic_search.server');

    $currentIndexPrefix = $config->get('index_prefix');

    //If neither the username or pass are blank, or both are blank, reset
    if (($form_state->getValue(['auth', 'password']) !== '' &&
         $form_state->getValue(['auth', 'user']) !== '') ||
        ($form_state->getValue(['auth', 'password']) === '' &&
         $form_state->getValue(['auth', 'user']) === '')
    ) {
      //TODO - hash pass
      $config->set('auth.password', $form_state->getValue(['auth', 'password']))
             ->set('auth.username',
                   $form_state->getValue(['auth', 'username']));
    }

    $config->set('scheme', $form_state->getValue('scheme'))
           ->set('host', $form_state->getValue('host'))
           ->set('port', $form_state->getValue('port'))
           ->set('kibana', $form_state->getValue('kibana'))
           ->set('index_prefix', $form_state->getValue('index_prefix'))
           ->set('advanced.developer.active',
                 $form_state->getValue(['advanced', 'developer', 'active']))
           ->set('advanced.developer.logging_channel',
                 $form_state->getValue([
                                         'advanced',
                                         'developer',
                                         'logging_channel',
                                       ]))
           ->set('advanced.validate.active',
                 $form_state->getValue(['advanced', 'validate', 'active']))
           ->set('advanced.validate.die_hard',
                 $form_state->getValue(['advanced', 'validate', 'die_hard']))
           ->set('advanced.pause', $form_state->getValue(['advanced', 'pause']))
           ->set('advanced.batch_size', $form_state->getValue(['advanced', 'batch_size']))
           ->set('advanced.index_batch_size', $form_state->getValue(['advanced', 'index_batch_size']))
           ->set('advanced.queue_update', $form_state->getValue(['advanced', 'queue_update']))
           ->set('advanced.queue_insert', $form_state->getValue(['advanced', 'queue_insert']))
           ->set('advanced.queue_delete', $form_state->getValue(['advanced', 'queue_delete']))
           ->save();

    //if the prefix has changed mark the indices as needing an update
    if ($form_state->getValue('index_prefix') !== $currentIndexPrefix) {
      /** @var \Drupal\elastic_search\Entity\ElasticIndexInterface[] $indices */
      $indices = ElasticIndex::loadMultiple();
      //create a batch to run markIndicesForServerUpdate
      $chunks = array_chunk($indices, 30);

      $this->executeBatch($chunks,
                          '\Drupal\elastic_search\Form\ServerForm::processIndexEntityUpdate',
                          '\Drupal\elastic_search\Controller\IndexController::finishBatch');
    }
  }

  /**
   * @param array  $chunks
   * @param string $opCallback
   * @param string $finishCallback
   * @param string $messageKey
   */
  protected function executeBatch(array $chunks, string $opCallback, string $finishCallback, string $messageKey = '') {

    $ops = [];
    foreach ($chunks as $chunkedIndices) {
      $ops[] = [$opCallback, [$chunkedIndices]];
    }
    $batch = new BatchDefinition($ops,
                                 $finishCallback,
                                 $this->t('Processing index ' . $messageKey . ' batch'),
                                 $this->t('Index ' . $messageKey . ' is starting.'),
                                 $this->t('Processed @current out of @total.'),
                                 $this->t('Encountered an error.')
    );
    batch_set($batch->getDefinitionArray());

  }

  /**
   * @param array $indices
   * @param array $context
   */
  public static function processIndexEntityUpdate(array $indices, array &$context) {

    if (!array_key_exists('progress', $context['sandbox'])) {
      $context['sandbox']['progress'] = 0;
    }

    foreach ($indices as $index) {
      $index->setNeedsUpdate();
      try {
        $index->save();
        $context['results'][] = $index;
      } catch (\Throwable $t) {
        \Drupal::logger('elastic.index')->error($t->getMessage());
      }
      $context['sandbox']['progress']++;
    }

  }

}
