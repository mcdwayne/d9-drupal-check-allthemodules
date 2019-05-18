<?php

namespace Drupal\commerce_xem;

use Drupal\commerce_xem\php2nem\NEM;

/**
 * Easy use of Nem API. 
 */
class NemApi {
  
  private static $instance;
  
  /**
   * Are we using TestNet or MainNet
   * 
   * @var boolean
   */
  private $isTest;
  
  /**
   * The MainNet servers
   * 
   * @var array
   */
  private static $servers = [
		'bigalice3.nem.ninja',
		'alice2.nem.ninja',
		'go.nem.ninja'
	];

  /**
   * The TestNet servers
   * 
   * @var array 
   */
	private static $testservers = [
		'bob.nem.ninja',
		'104.128.226.60',
		'192.3.61.243'
	];
  
  private $nem;
  
  static function getServers() {
    return self::$servers;
  }
  
  function getNem() {
    return $this->nem;
  }

  public static function getInstance($isTest = FALSE) {
		if ( null === self::$instance ) {
			self::$instance = new self();

      if ($isTest) {
        self::$servers = self::$testservers;
      }
		}
		return self::$instance;
	}
  
  /**
   * Send a Xem WS call to the right servers. 
   * 
   * @param string $path
   *  The WS path
   * 
   * @return string $json
   */
  private function _nemSend($path) {
    foreach ($this->getServers() as $server){
      $conf = [
        'nis_address' => $server
      ];
      self::$instance->nem = new Nem($conf);
      $content = self::$instance->nem->nis_get($path);
      if (!empty($content)) {
        break;
      }
    }
    return $content;
  }
  
  /**
   * Get account info
   * 
   * @see https://nemproject.github.io/
   * @param $string $address
   * @return string $json
   */
  public function getAccountInfo($address) {
    $path = 'account/get?address=' . $address;
    return self::$instance->_nemSend($path);
  }
  
  /**
   * Get latest transactions
   * 
   * @see https://nemproject.github.io/
   * @param string $address
   * @return string $json
   */
  public function getLatestTransactions($address) {
    $path = 'account/transfers/incoming?address=' . $address;
    return self::$instance->_nemSend($path);
	}
  
}
