<?php
namespace Drupal\sfs;

use Drupal\comment\Entity\Comment;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

class ReportSpam {
    
  use StringTranslationTrait;
  
  /**
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;
  
  /**
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $log;
  
  /**
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;
  
  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;
  
  /**
   * @var \Drupal\sfs\SfsRequest
   */
  protected $sfsRequest;
  
  /**
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * ReportSpam constructor.
   * 
   * @param ConfigFactoryInterface $config_factory
   * @param LoggerChannelFactoryInterface $logger
   * @param ClientInterface $http_client
   * @param SfsRequest $sfs_request
   * @param MessengerInterface $messenger
   */
  public function __construct(
    ConfigFactoryInterface $config_factory, 
    LoggerChannelFactoryInterface $logger, 
    ClientInterface $http_client, 
    EntityTypeManagerInterface $entity_type_manager, 
    SfsRequest $sfs_request, 
    MessengerInterface $messenger
   ) {
    $this->config = $config_factory->get('sfs.settings');
    $this->log = $logger->get('sfs');
    $this->httpClient = $http_client;
    $this->entityTypeManager = $entity_type_manager;
    $this->sfsRequest = $sfs_request;
    $this->messenger = $messenger;
  }
  
  /**
   * Report for a comment.
   * 
   * @param int $comment_id
   */
  public function commentReport($comment_id = 0) {
    $token = $this->config->get('sfs_api_key');
    $comment = Comment::load($comment_id);
    /** @var \Drupal\user\Entity\User $user */
    $user = $comment->getOwner();
    if(empty($token)) {
      $this->messenger->addWarning($this->t('No api key found for reporting to stopforumspam.com. Cannot report without a valid key.'));
    }
    elseif ($user->isAnonymous()) {
      $this->messenger->addWarning($this->t('Anonymous users should not be reported to stopforumspam.com. Comment is not reported.'));
    }
    else {
      $name = $user->getUsername();
      $mail = $user->getEmail();
      $subject = $comment->getSubject();
      $body = $comment->get('comment_body')->value;
      $ip = $comment->getHostname();
      
      if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === FALSE) {
        $message = $this->t('Invalid IP address on comment: @ip. SFS Client will not report invalid ip addresses or ip addresses within private or reserved ip ranges.', ['@ip' => $ip]);
        $this->messenger->addStatus($message);
        $this->log->notice($message);
      }
      else {
        $evidence = '<p>' . $subject . '</p>' . $body;
        if ($this->reportSpam($token, $name, $mail, $ip, $evidence)) {
          $this->messenger->addStatus($this->t('The comment @id was reported successfully to stopforumspam.com.', ['@id' => $comment_id]));
          try {
            $comment->setUnpublished();
            $comment->save();
            $this->log->notice($this->t('The comment @id was unpublished.', ['@id' => $comment_id]));
          }
          catch (EntityStorageException $e) {
            $this->messenger->addError($this->t('Failed to unpublish comment @id: @error', ['@id' => $comment_id, '@error' => $e->getMessage()]));
            $this->log->error('Failed to unpublish comment @id: @error', ['@id' => $comment_id, '@error' => $e->getMessage()]);
          }
        }
      }
    }
  }
  
  /**
   * Report for a node.
   * 
   * @param int $node_id
   */
  public function nodeReport($node_id = 0) {
    $token = $this->config->get('sfs_api_key');
    $node = Node::load($node_id);
    /** @var \Drupal\user\Entity\User $user */
    $user = $node->getOwner();
    $ip_address = $this->sfsRequest->getHostname($node_id, 'node');
    if(empty($token)) {
      $this->messenger->addWarning($this->t('No api key found for reporting to stopforumspam.com. Cannot report without a valid key.'));
    }
    elseif ($user->isAnonymous()) {
      $this->messenger->addWarning($this->t('Anonymous users should not be reported to stopforumspam.com. Comment is not reported.'));
    }
    elseif ($ip_address) {
      $name = $user->getUsername();
      $mail = $user->getEmail();
      $subject = $node->getTitle();
      $body = $node->get('body')->value;
      
      if (filter_var($ip_address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === FALSE) {
        $message = $this->t('Invalid IP address on comment: @ip. SFS Client will not report invalid ip addresses or ip addresses within private or reserved ip ranges.', ['@ip' => $ip_address]);
        $this->messenger->addStatus($message);
        $this->log->notice($message);
      }
      else {
        $evidence = '<p>' . $subject . '</p>' . $body;
        if ($this->reportSpam($token, $name, $mail, $ip_address, $evidence)) {
          $this->messenger->addStatus($this->t('The content entity @id was reported successfully to stopforumspam.com.', ['@id' => $node_id]));
          try {
            $node->setUnpublished();
            $node->save();
            $this->messenger->addStatus($this->t('The content entity @id was unpublished.', ['@id' => $node_id]));
          }
          catch (EntityStorageException $e) {
            $this->messenger->addError($this->t('Failed to unpublish content entity @id: @error', ['@id' => $node_id, '@error' => $e->getMessage()]));
            $this->log->error('Failed to unpublish content entity @id: @error', ['@id' => $node_id, '@error' => $e->getMessage()]);
          }
        }
      }
    }
    else {
      $this->messenger->addWarning($this->t('No ip address found for this content entity. Content entity is not reported.'));
    }
  }
  
  /**
   * Report for a user.
   * 
   * @param number $user_id
   */
  public function userReport($user_id = 0) {
    $token = $this->config->get('sfs_api_key');
    if(empty($token)) {
      $this->messenger->addWarning($this->t('No api key found for reporting to stopforumspam.com. Cannot report without a valid key.'));
    }
    else {
      $user = User::load($user_id);
      $name = $user->getAccountName();
      $mail = $user->getEmail();
      $ipAddresses = $this->sfsRequest->getUserIpAddresses($user_id);
      $evidence = $this->getEvidence($user_id);
      if (empty($ipAddresses)) {
        $this->messenger->addWarning($this->t('No ip address found for this user. User cannot be reported.'));
      }
      foreach ($ipAddresses as $ip) {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === FALSE) {
          $message = $this->t('Invalid IP address on comment: @ip. SFS Client will not report invalid ip addresses or ip addresses within private or reserved ip ranges.', ['@ip' => $ip]);
          $this->messenger->addStatus($message);
          $this->log->notice($message);
        }
        elseif($this->reportSpam($token, $name, $mail, $ip, $evidence)) {
          $this->messenger->addStatus($this->t('Account with username: @name, e-mail: @mail and ip: @ip has successfully been reported to stopforumspam.com.', ['@name' => $name, '@mail' => $mail, '@ip' => $ip]));
        }
      }
    }
  }
  
  protected function getEvidence($uid) {
    $evidence = '';
    $commentStorage = $this->entityTypeManager->getStorage('comment');
    $ids = $commentStorage->getQuery()
      ->condition('uid', $uid)
      ->execute();
    $comments = $commentStorage->loadMultiple($ids);
    /* @var \Drupal\comment\Entity\Comment $comment */
    foreach ($comments as $comment) {
      $evidence .= date('Y-m-d H:i:s', $comment->getCreatedTime()) . PHP_EOL;
      $evidence .= 'Subject: ' . $comment->getSubject() . PHP_EOL;
      $evidence .= $comment->get('comment_body')->value . PHP_EOL . PHP_EOL;
      try {
        $comment->setUnpublished()->save();
        $this->messenger->addStatus($this->t('The comment @id was unpublished and is now waiting for approval.', ['@id' => $comment->id()]));
      }
      catch (EntityStorageException $e) {
        $this->messenger->addError($this->t('Failed to unpublish comment @id: @error', ['@id' => $comment->id(), '@error' => $e->getMessage()]));
        $this->log->error('Failed to unpublish comment @id: @error', ['@id' => $comment->id(), '@error' => $e->getMessage()]);
      }
    }
    
    $nodeStorage = $this->entityTypeManager->getStorage('node');
    $ids = $nodeStorage->getQuery()
    ->condition('uid', $uid)
    ->execute();
    $nodes = $nodeStorage->loadMultiple($ids);
    /* @var \Drupal\node\Entity\Node $node */
    foreach ($nodes as $node) {
      $evidence .= date('Y-m-d H:i:s', $node->getCreatedTime()) . PHP_EOL;
      $evidence .= 'Subject: ' . $node->getTitle() . PHP_EOL;
      $evidence .= $node->get('body')->value . PHP_EOL . PHP_EOL;
      try {
        $node->setUnpublished()->save();
        $this->messenger->addStatus($this->t('The content entity @id was unpublished.', ['@id' => $node->id()]));
      }
      catch (EntityStorageException $e) {
        $this->messenger->addError($this->t('Failed to unpublish content entity @id: @error', ['@id' => $node->id(), '@error' => $e->getMessage()]));
        $this->log->error('Failed to unpublish content entity @id: @error', ['@id' => $node->id(), '@error' => $e->getMessage()]);
      }
    }
    
    return $evidence;
  }
  
  /**
   * Report spammer to www.stopforumspam.com.
   * 
   * @param string $token
   * @param string $name
   * @param string $mail
   * @param string $ip_address
   * @param string $evidence
   * @return boolean
   */
  protected function reportSpam($token, $name, $mail, $ip_address, $evidence) {
    $name = urlencode($name);
    $mail = urlencode($mail);
    $evidence = urlencode($evidence);
    $data = "username={$name}&ip_addr={$ip_address}&evidence={$evidence}&email={$mail}&api_key={$token}";
    $options = [
      'headers' => [
        'Content-type' => 'application/x-www-form-urlencoded',
        'Content-length' => strlen($data),
        'Connection' => 'close',
      ],
      'body' => $data,
    ];
    if ($this->config->get('sfs_http_secure')) {
      $url = 'https://www.stopforumspam.com/add.php';
    }
    else {
      $url = 'http://www.stopforumspam.com/add.php';
    }
    
    try {
      $response = $this->httpClient->request('POST', $url, $options);
      $data = (string) $response->getBody();
    }
    catch (GuzzleException $e) {
      $this->messenger->addError($this->t('Failed to report spam due to "%error".', ['%error' => $e->getMessage()]));
      return FALSE;
    }
    
    return TRUE;
  }
}
