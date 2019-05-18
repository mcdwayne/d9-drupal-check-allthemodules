<?php

namespace Drupal\open_connect\Plugin\OpenConnect\Provider;

/**
 * Define the QQ identity provider.
 *
 * @OpenConnectProvider(
 *   id = "qq",
 *   label = @Translation("QQ"),
 *   description = @Translation("QQ Open Platform"),
 *   homepage = "http://open.qq.com",
 *   urls = {
 *     "authorization" = "https://graph.qq.com/oauth2.0/authorize",
 *     "access_token" = "https://graph.qq.com/oauth2.0/token",
 *     "openid" = "https://graph.qq.com/oauth2.0/me",
 *     "user_info" = "https://graph.qq.com/user/get_user_info",
 *   },
 * )
 */
class QQ extends ProviderBase {

//  /**
//   * {@inheritdoc}
//   *
//   * @see http://wiki.open.qq.com/wiki/website/%E4%BD%BF%E7%94%A8Authorization_Code%E8%8E%B7%E5%8F%96Access_Token
//   *
//   * For mobile, set a 'display' query parameter in redirect url.
//   */
//  protected function processRedirectUrlOptions(array &$options) {
//    // (Optional) Add in the display parameter, available values:
//    // - mobile
//    // $options['query']['display'] = 'mobile';
//  }

  /**
   * {@inheritdoc}
   *
   * Response examples:
   *
   * success:
   *   access_token=ACCESS_TOKEN&expires_in=7776000
   *
   * failure:
   *   callback( {"error":100004,"error_description":"param grant_type is wrong or lost "} );
   */
  protected function doFetchToken($url, array $params) {
    // Perform a GET request.
    $response = $this->httpClient->get($url, [
      'query' => $params,
    ]);

    return $this->parseResponseData((string) $response->getBody());
  }

  /**
   * {@inheritdoc}
   *
   * Response examples:
   *
   * success:
   *   callback( {"client_id":"APPID","openid":"OPENID"} );
   *
   * failure:
   *   callback( {"error":100007,"error_description":"param access token is wrong or lost "} );
   */
  protected function doFetchOpenid($url, array $params) {
    $response = $this->httpClient->get($url, [
      'query' => $params,
    ]);

    return $this->parseResponseData((string) $response->getBody());
  }

  /**
   * {@inheritdoc}
   *
   * Response examples:
   *
   * success:
   * {
   *   "ret":0,
   *   "msg":"",
   *   "nickname":"Peter",
   *   "figureurl":"http://qzapp.qlogo.cn/qzapp/111111/942FEA70050EEAFBD4DCE2C1FC775E56/30",
   *   "figureurl_1":"http://qzapp.qlogo.cn/qzapp/111111/942FEA70050EEAFBD4DCE2C1FC775E56/50",
   *   "figureurl_2":"http://qzapp.qlogo.cn/qzapp/111111/942FEA70050EEAFBD4DCE2C1FC775E56/100",
   *   "figureurl_qq_1":"http://q.qlogo.cn/qqapp/100312990/DE1931D5330620DBD07FB4A5422917B6/41",
   *   "figureurl_qq_2":"http://q.qlogo.cn/qqapp/100312990/DE1931D5330620DBD07FB4A5422917B6/100",
   *   "is_yellow_vip"："1",
   *   "is_yellow_year_vip":"0",
   *   "yellow_vip_level":"6"
   * }
   */
  protected function doFetchUserInfo($url, array $params) {
    // Add in the oauth_consumer_key parameter.
    $params['oauth_consumer_key'] = $this->configuration['client_id'];
    $response = $this->httpClient->get($url, [
      'query' => $params,
    ]);
    return \GuzzleHttp\json_decode($response->getBody(), TRUE);
  }

  /**
   * {@inheritdoc}
   */
  protected function isResponseSuccessful(array $response) {
    return empty($response['error']);
  }

  /**
   * {@inheritdoc}
   */
  protected function getResponseError(array $response) {
    if (isset($response['error'], $response['error_description'])) {
      return sprintf('%s: %s', $response['error'], $response['error_description']);
    }
    return '';
  }

  /**
   * Parses the response data.
   *
   * @param $data
   *   The raw response data.
   *
   * @return array
   *   An array of parsed data values.
   */
  private function parseResponseData($data) {
    if(strpos($data, 'callback') !== FALSE){
      $lpos = strpos($data, '(');
      $rpos = strrpos($data, ')');
      $data  = substr($data, $lpos + 1, $rpos - $lpos -1);
      $result = \GuzzleHttp\json_decode($data, TRUE);
    }
    else {
      parse_str($data, $result);
    }
    return $result;
  }

}
