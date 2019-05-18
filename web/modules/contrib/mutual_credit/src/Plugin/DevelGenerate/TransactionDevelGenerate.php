<?php

namespace Drupal\mcapi\Plugin\DevelGenerate;

use Drupal\mcapi\Mcapi;
use Drupal\mcapi\Entity\Transaction;
use Drupal\mcapi\Entity\Wallet;
use Drupal\devel_generate\DevelGenerateBase;
use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a DevelGenerate plugin.
 *
 * @DevelGenerate(
 *   id = "mcapi_transaction",
 *   label = @Translation("transactions"),
 *   description = @Translation("Generate a given number of transactions..."),
 *   url = "transaction",
 *   permission = "administer devel_generate",
 *   settings = {
 *     "num" = 100,
 *     "kill" = TRUE,
 *     "type" = "default"
 *   }
 * )
 */
class TransactionDevelGenerate extends DevelGenerateBase implements ContainerFactoryPluginInterface {

  const MAX = 100;

  /**
   * The transaction storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $transactionStorage;

  /**
   * The transaction storage.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQueryFactory;

  /**
   * All the wallet ids
   *
   * @var array
   */
  private $wids;

  /**
   * Constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param array $plugin_definition
   *   Definition of the plugin.
   * @param \Drupal\Core\Entity\EntityStorageInterface $transaction_storage
   *   The transaction storage.
   * @param Drupal\Core\Entity\Query\QueryFactory $entity_query_factory
   *
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EntityStorageInterface $transaction_storage, QueryFactory $entity_query_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->transactionStorage = $transaction_storage;
    $this->entityQueryFactory = $entity_query_factory;
    $this->wids = $this->entityQueryFactory->get('mcapi_wallet')->execute();

    if (count($this->wids) < 2) {
      throw new \Exception('Not enough wallets to make a transaction.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('entity.manager')->getStorage('mcapi_transaction'),
      $container->get('entity.query')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['kill'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('<strong>Delete all transactions</strong> before generating new content.'),
      '#default_value' => $this->getSetting('kill'),
    ];
    $form['num'] = [
      '#type' => 'number',
      '#title' => $this->t('How many transactions would you like to generate?'),
      '#default_value' => $this->getSetting('num'),
      '#required' => TRUE,
      '#min' => 0,
    ];
    $form['type'] = [
      '#title' => $this->t('What type of transactions'),
      '#type' => 'select',
      '#options' => Mcapi::entityLabelList('mcapi_type'),
      '#default_value' => $this->getSetting('type'),
      '#required' => TRUE,
      '#min' => 0,
    ];
    $form['#redirect'] = FALSE;
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function generateElements(array $values) {
    $this->settings = $values + $this->settings;
    if ($this->getSetting('num') <= static::MAX) {
      if ($this->getSetting('kill')) {
        $this->contentKill();
      }
      for ($i = 1; $i < $this->getSetting('num'); $i++) {
        $this->develGenerateTransactionAdd();
      }
      static::sortTransactions();
      if (function_exists('drush_log') && $i % drush_get_option('feedback', 1000) == 0) {
        drush_log(dt('Completed @feedback transactions ', ['@feedback' => drush_get_option('feedback', 1000)], 'ok'));
      }
      $this->setMessage($this->t('Created @num transactions.', ['@num' => $this->getSetting('num')]));
    }
    else {
      // setFile doesn't include the file yet and devel_generate_batch_finished must be callable
      module_load_include('batch.inc', 'devel_generate');
      $batch_builder = (new BatchBuilder())
       ->setTitle(t('Generating transactions'))
       ->setFile(drupal_get_path('module', 'devel_generate') . '/devel_generate.batch.inc')
       ->setFinishCallback('devel_generate_batch_finished');
      // Add the kill operation.
      if ($this->getSetting('kill')) {
        $batch_builder->addOperation('devel_generate_operation', [$this, 'batchContentKill', []]);
      }

      $batches = ceil($this->getSetting('num')/static::MAX);
      for ($num = 0; $num < $batches; $num++) {
        $batch_builder->addOperation('devel_generate_operation', [$this, 'batchContentAddTransactions', []]);
      }

      $batch_builder->addOperation([$this, 'sortTransactions']);
      batch_set($batch_builder->toArray());
    }
  }


  /**
   * batch callback to create one transaction.
   */
  public function batchContentAddTransactions($values, &$context) {
    $i = 0;
    if (!isset($context['results']['num'])) {
      $context['results']['num'] = 0;
    }
    while ($context['results']['num'] < $this->getSetting('num') and $i < Self::MAX) {
      $this->develGenerateTransactionAdd();
      $context['results']['num']++;
      $i++;
    }
  }


  /**
   * batch callback to create one transaction.
   */
  public function batchContentKill($values, &$context) {
    $this->contentKill();
  }

  /**
   * Deletes all transactions .
   *
   * @param string $type
   *   The type of transactions to delete
   *
   * @note Loads all transactions into memory at the same time.
   */
  public function contentKill() {
    $xids = $this->entityQueryFactory->get('mcapi_transaction')
      ->condition('type', $this->getSetting('type'))
      ->execute();
    if (!empty($xids)) {
      $transactions = Transaction::loadMultiple($xids);
      $this->transactionStorage->delete($transactions);
      $this->setMessage($this->t('Deleted %count transactions.', array('%count' => count($xids))));
    }
  }

  /**
   * Create one transaction. Used by both batch and non-batch code branches.
   *
   * @note this may attempt to send a email for pending transactions.
   */
  public function develGenerateTransactionAdd() {
    list($w1, $w2) = $this->get2RandWalletIds();
    if (!$w2) {
      return;
    }
    $props = [
      'payer' => $w1,
      'payee' => $w2,
      // Transactions of type 'auto' don't show in the default view.
      'type' => $this->getSetting('type') ?: 'default',
      'creator' => 1,
      'description' => $this->getRandom()->sentences(1),
      'uid' => $w1
    ];

    // find a currency that's common to both wallets.
    $payer_currencies = mcapi_currencies_available(Wallet::load($props['payer']));
    $payee_currencies = mcapi_currencies_available(Wallet::load($props['payee']));
    $curr_ids = array_intersect_key($payer_currencies, $payee_currencies);
    if (!$curr_ids) {
      // Fail silently.
      return;
    }
    $currency = $curr_ids[array_rand($curr_ids)];
    $props['worth'] = [
      'curr_id' => $currency->id(),
      'value' => $currency->sampleValue()
    ];
    $transaction = Transaction::create($props);
    $this->populateFields($transaction);
    // We're not using generateExampleData here because it makes a mess.
    // But that means we might miss other fields on the transaction.

    // Change the created time of the transactions, coz they mustn't be all in
    // the same second.
    $transaction->save();
    if ($transaction->state->target_id == 'pending') {
      // Signatures already exist because they were created in presave phase.
      foreach ($transaction->signatures as $uid => $signed) {
        // Leave 1 in 10 signatures unsigned.
        if (rand(0, 9) > 0) {
          // Don't inject this service because the signatures module  might not be enabled.
          \Drupal::service('mcapi.signatures')->setTransaction($transaction)->sign($uid);
        }
      }
    }
    $transaction->created->value = $this->randTransactionTime($w1, $w2);
    // NB this could generate pending emails.
    $transaction->save();
  }

  /**
   * Get two random wallets
   *
   * @return int[]
   *   2 wallet ids
   */
  public function get2RandWalletIds() {
    $wids = $this->wids;
    shuffle($wids);
    return array_slice($wids, -2);
  }

  /**
   * Get a time that a transaction could have taken place between 2 wallets
   * @param type $wid1
   *   The first wallet ID.
   * @param type $wid2
   *   The second wallet ID.
   * @return integer
   *   The unixtime
   */
  public function randTransactionTime($wid1, $wid2) {
    //get the youngest wallet and make a time between its creation and now.
    $wallets = Wallet::loadMultiple([$wid1, $wid2]);
    $latest = max($wallets[$wid1]->created->value, $wallets[$wid2]->created->value);
    return rand($latest, REQUEST_TIME);
  }

  public static function sortTransactions() {
    $db = \Drupal::database();
    $times = $db->select('mcapi_transaction', 't')
      ->fields('t', ['serial', 'created'])
      ->execute()->fetchAllKeyed();
    $serials = array_keys($times);
    sort($serials);
    sort($times);
    $new = array_combine($serials, $times);
    foreach ($new as $serial => $created) {
      //assuming that $created is unique and clashes are extremely unlikely
      $db->update('mcapi_transaction')
        ->fields(['serial' => $serial])
        ->condition('created', $created)
        ->execute();
      $db->update('mcapi_transactions_index')
        ->fields(['serial' => $serial])
        ->condition('created', $created)
        ->execute();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateDrushParams($args) {
    $values['kill'] = drush_get_option('kill');
    $values['type'] = drush_get_option('type');
    $values['num'] = array_shift($args);
    return $values;
  }
}
