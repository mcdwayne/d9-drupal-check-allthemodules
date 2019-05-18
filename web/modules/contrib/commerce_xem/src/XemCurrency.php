<?php


namespace Drupal\commerce_xem;

use Drupal\Component\Serialization\Json;

/**
 * Conversion from Drupal Commerce currencies to Xem. 
 */
class XemCurrency {
  
  /**
   * The coin market cap URL for conversion. 
   * 
   * @var string $url
   */
  const COIN_MARKET_CAP_URL = 'https://api.coinmarketcap.com/v2/ticker/873';
  
  
  /**
   * Get current Xem Data from Coin Market Cap
   * 
   * An order object
   * @param Drupal\commerce_order\Entity\Order $order
   * 
   * If TRUE : save this Xem amount with the order
   * @param boolean $save
   * 
   * Full Xem data information, with the Xem amount
   * @return array xemData
   */
  public static function getCurrentXemData(\Drupal\commerce_order\Entity\Order $order, $save = FALSE) {
    $xemData = $order->getData('xemData');
    if (empty($xemData) || TRUE) {
      $price = $order->getTotalPrice();
      $currencyCode = $price->getCurrencyCode();
      $client = \Drupal::httpClient();
      $response =  
      $client->request('GET', XemCurrency::COIN_MARKET_CAP_URL, [
        'query' => [
          'convert' => $currencyCode
        ]
      ]);

      if (!$response || $response->getStatusCode() != 200) {
        return FALSE;
      }

      if (empty($response->getBody())) {
        return FALSE;
      }

      $json = $response->getBody()->getContents();
      $jsonDecoded = Json::decode($json);
      $xemData = reset($jsonDecoded);
      if ($save) {
        $order->setData('xemData', $xemData);
        $order->save();
      }
    }
    return $xemData;
  }
  
  /**
   * Convert a Drupal Commerce Price to a Xem price
   * 
   * An order object
   * @param Drupal\commerce_order\Entity\Order $order
   * 
   * If TRUE : save this Xem amount with the order
   * @param boolean $save
   * 
   * The Xem price
   * @return float $xemPrice
   */
  public static function convertToXem(\Drupal\commerce_order\Entity\Order $order, $save = FALSE) {
    $xemData = XemCurrency::getCurrentXemData($order, $save);
    $currencyCode = $order->getTotalPrice()->getCurrencyCode();
    $xemPrice = $order->getTotalPrice()->getNumber() / floatval($xemData['quotes'][$currencyCode]['price']);
    return round($xemPrice, 2);
  }
}