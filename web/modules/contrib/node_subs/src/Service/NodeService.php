<?php

namespace Drupal\node_subs\Service;


use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Link;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\node\Entity\Node;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;


class NodeService {

  use StringTranslationTrait;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * The database connection object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * @var Configuration
   */
  protected $config;
  /**
   * @var Configuration
   */
  protected $textConfig;

  /**
   * The time interface object.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The node_subs account object.
   *
   * @var \Drupal\node_subs\Service\AccountService
   */
  protected $account;

  /**
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * Constructs a new AccountService object.
   *
   * * @param \Drupal\Core\Database\Connection $connection
   *   The database connection object.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The parser to use when extracting message variables.
   */
  public function __construct($node_subs_account, LoggerChannelFactoryInterface $logger_factory, Connection $connection, ConfigFactoryInterface $config_factory, TimeInterface $time, ModuleHandler $module_handler, TranslationInterface $string_translation) {
    $this->account = $node_subs_account;
    $this->logger = $logger_factory->get('node_subs');
    $this->connection = $connection;
    $this->configFactory = $config_factory;
    $this->config = $config_factory->get('node_subs.nodesettings');
    $this->textConfig = $config_factory->get('node_subs.textsettings');
    $this->time = $time;
    $this->moduleHandler = $module_handler;
    $this->moduleHandler = $module_handler;
    $this->stringTranslation = $string_translation;
  }

  public function checkType($type) {
    return $this->configFactory->get("node_subs.nodesettings.$type");
  }

  public function getFieldName($type) {
    return $this->config->get($type)['field'] ?? 'body';
  }

  public function getViewMode($type) {
    return $this->config->get($type)['view_mode'] ? $this->config->get($type)->get('view_mode') : 'teaser';
  }

  public function getViewModeList() {
    // todo: why do we load all available modes instead of loading current bundle related ones?
    $list = &drupal_static(__FUNCTION__, array());
    if (empty($list)) {
      $nodeEntity = \Drupal::service('entity_display.repository');
      $view_modes = $nodeEntity->getViewModes('node');
      foreach ($view_modes as $view_mode => $view_mode_data) {
        $list[$view_mode] = $view_mode_data['label'];
      }
    }
    return $list;
  }

  public function loadNodeData($nid, $table) {
    $node_subs_nodes = &drupal_static(__FUNCTION__, array());
    if (empty($node_subs_nodes[$table][$nid])) {
      $query = $this->connection->select($table, 'tab')
        ->condition('tab.nid', $nid)
        ->fields('tab')
        ->range(0, 1);
      $node_subs_nodes[$table][$nid] = $query->execute()->fetchObject();
    }

    return $node_subs_nodes[$table][$nid];
  }

  public function loadQueue($nid) {
    $node_subs_node = $this->loadNodeData($nid, NODE_SUBS_QUEUE_TABLE);
    if ($node_subs_node) {
      $node_subs_node->status = 'queue';
    }
    return $node_subs_node;
  }

  public function getQueue($table, $limit = 10) {
    $node_subs_queue_nodes = &drupal_static(__FUNCTION__, array());
    if (empty($node_subs_nodes[$table])) {
      $query = $this->connection->select($table, 'tab')
        ->fields('tab');
      if ($limit) {
        $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(20);
      }
      $node_subs_nodes[$table] = $query->execute()->fetchAll();
    }
    return $node_subs_nodes[$table];
  }

  public function loadHistory($nid) {
    $node_subs_node = $this->loadNodeData($nid, NODE_SUBS_HISTORY_TABLE);
    if ($node_subs_node) {
      $node_subs_node->status = 'history';
    }
    return $node_subs_node;
  }

  public function getHistoryNodes($table, $limit = 10) {
    $history_nodes = &drupal_static(__FUNCTION__, array());
    if (empty($history_nodes[$table])) {
      $query = $this->connection->select($table, 'tab')
        ->fields('tab');
      if ($limit) {
        $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit($limit);
      }
      $history_nodes[$table] = $query->execute()->fetchAll();
    }
    return $history_nodes[$table];
  }

  public function checkNode($nid) {
    $status = 'ready';
    if ($this->loadHistory($nid)) {
      $status = 'history';
    }
    elseif ($this->loadQueue($nid)) {
      $status = 'queue';
    }
    return $status;
  }

  public function addQueue($nid) {
    if ($exists_node = $this->loadQueue($nid)) {
      $exists_node->process++;
      $this->connection->merge(NODE_SUBS_QUEUE_TABLE)
        ->key(['nid' => $nid])
        ->fields($exists_node)
        ->execute();
      $node_subs_node = $exists_node;
    }
    else {
      $node_subs_node = [];
      $node_subs_node['nid'] = $nid;
      $node_subs_node['created'] = $this->time->getRequestTime();
      $node_subs_node['process'] = 0;
      $this->connection->merge(NODE_SUBS_QUEUE_TABLE)
        ->key(['nid' => $nid])
        ->fields($node_subs_node)
        ->execute();
    }
    return $node_subs_node;
  }

  public function deleteQueue($nid) {
    $this->connection->delete(NODE_SUBS_QUEUE_TABLE)
      ->condition('nid', $nid)
      ->execute();
  }

  public function deleteHistory($nid) {
    $this->connection->delete(NODE_SUBS_HISTORY_TABLE)
      ->condition('nid', $nid)
      ->execute();
  }

  public function queueNext() {
    $node_subs_node = $this->connection->select(NODE_SUBS_QUEUE_TABLE, 'queue')
      ->fields('queue')
      ->orderBy('queue.process', 'DESC')
      ->orderBy('queue.created', 'ASC')
      ->range(0, 1)
      ->execute()->fetchObject();
    return $node_subs_node;
  }

  public function getFromQueue($nids) {
    $node_subs_node = $this->connection->select(NODE_SUBS_QUEUE_TABLE, 'queue')
      ->fields('queue')
      ->condition('nid', $nids, 'IN')
      ->execute()->fetchAll();
    return $node_subs_node;
  }

  public function moveToHistory($nid) {
    $node_subs_node = $this->loadQueue($nid);
    if (!$node_subs_node) {
      $this->logger->info('Node !nid can\'t be moved to history because it does not not exist in the queue table', array('!nid' => $nid));
      return FALSE;
    }
    $this->deleteQueue($nid);
    $this->deleteHistory($nid);
    $node_subs_node->send = $this->time->getRequestTime();
    if (isset($node_subs_node->process)) {
      unset($node_subs_node->process);
    }
    $this->connection->merge(NODE_SUBS_HISTORY_TABLE)
      ->key(['nid' => $nid])
      ->fields([
        'nid' => $node_subs_node->nid,
        'created' => $node_subs_node->created,
        'send' => $node_subs_node->send,
      ])
      ->execute();

    // Delete marked for deletion accounts.
    $this->account->deleteRecords();

    return $node_subs_node;
  }

  public function queueProcess(array $nids = []) {
    $node_subs_nodes = $nids ? $this->getFromQueue($nids) : $this->queueNext();
    if (!$node_subs_nodes) {
      return;
    }
    if (is_object($node_subs_nodes)) {
      $node_subs_nodes = [$node_subs_nodes];
    }

    foreach ($node_subs_nodes as $node_subs_node) {
      $accounts = $this->account->getBatch($node_subs_node->process);
      $message = $this->prepareText($node_subs_node->nid);

      if ($this->account->checkProgress($node_subs_node->process)) {
        $this->addQueue($node_subs_node->nid);
      }
      else {
        $this->moveToHistory($node_subs_node->nid);
      }
      $message['nid'] = $node_subs_node->nid;

      $this->moduleHandler->alter('node_subs_queue_process', $accounts, $message);

      $this->moduleHandler->invokeAll('node_subs_queue_process', [$accounts, $message]);
    }
  }

  public function addToProcess($node) {
    if ($this->checkType($node->getType())) {
      $status = $this->checkNode($node->id());
      $message_options = array('@title' => $node->getTitle());
      if ($status == 'ready' && $node->isPublished()) {
        $this->addQueue($node->id());
        drupal_set_message(t('Node @title added form subscribe queue', $message_options));
      }
      elseif ($status == 'queue' && !$node->isPublished()) {
        $this->deleteQueue($node->id());
        drupal_set_message(t('Node @title removed form subscribe queue', $message_options));
      }
    }
  }

  public function getText($type) {
    // todo: deal with it.
    $text = $this->textConfig->get($type);
    if (!$text) {
      $default_texts = &drupal_static(__FUNCTION__, []);
      if (empty($default_texts)) {
        $default_texts = $this->textConfig->get();
      }
      if (isset($default_texts[$type])) {
        $text = $default_texts[$type];
      }
    }
    if (!$text) {
      $this->logger->info('Text for key !key undefined', ['!key' => $type]);
      return $this->t('Text undefined');
    }
    else {
      return $this->t($text);
    }
  }

  public function prepareText($nid) {
    // todo: inject?
    $node = Node::load($nid);
    $text = [];
    $field_name = $this->getFieldName($node->getType());
    if (empty($node->{$field_name}->getValue())) {
      $field_name = 'body';
    }

    if (!empty($node->{$field_name}->getValue())) {
      $display = $this->getViewMode($node->getType());
      $text['body'] = $node->{$field_name}->view($display);
      $text['body']['#weight'] = -50;
    }
    $text['more'] = array(
      '#prefix' => '<br>',
      '#markup' => Link::createFromRoute($this->t('Read more'), 'entity.node.canonical', ['node' => $node->id()], ['absolute' => TRUE])->toString(),
      '#suffix' => '<br>',
      '#weight' => 50
    );

    return [
      'subject' => $node->getTitle(),
      'body' => \Drupal::service('renderer')->render($text),
    ];
  }


}