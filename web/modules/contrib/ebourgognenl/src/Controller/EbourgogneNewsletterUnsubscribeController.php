<?php



namespace Drupal\ebourgognenewsletter\Controller;



use \Drupal\Component\Utility\UrlHelper;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ClientException;

use \Symfony\Component\HttpFoundation\RedirectResponse;

define('EBOU_NEWS_BO_BASE_URL', \Drupal::config('ebourgognenewsletter.settings')->get('ebou_news_bo_base_url'));
define('EBOU_NEWS_PROXY', \Drupal::config('ebourgognenewsletter.settings')->get('ebou_news_proxy'));
define('EBOU_NEWS_BO_API_URL', EBOU_NEWS_BO_BASE_URL . 'api/newsletter/');
define('EBOU_NEWS_BO_API_UNSUBSCRIBE_URL', EBOU_NEWS_BO_API_URL . 'unsubscribe/');

/**
 * Controller for PHPUnit description page.
 */
class EbourgogneNewsletterUnsubscribeController {

  /**
   * Displays a page with a descriptive page.
   *
   * Our router maps this method to the path 'examples/simpletest_example'.
   */
  public function unsubscribe($follower_id, $newsletter_id) {
    /*$build = array(
    '#markup' => t('This Simpletest Example is designed to give an introductory tutorial to writing
    a simpletest test. Please see the <a href="http://drupal.org/node/890654">associated tutorial</a>.') . $follower_id,
    );
    return $build;*/

    return $this->ebourgognenewsletter_unsubscribe($follower_id, $newsletter_id);

  }

  /**
   *
   */
  function ebourgognenewsletter_unsubscribe($follower_id, $newsletter_id) {

    // Retrieval of token parameter.
    $parameters = UrlHelper::filterQueryParameters(\Drupal::request()->query->all(), array('sort', 'order'));

    $token = '';

    if (isset($parameters['token'])) {
      $token = $parameters['token'];
    }

    $base_url = EBOU_NEWS_BO_API_UNSUBSCRIBE_URL . $follower_id . '/from/' . $newsletter_id . '?token=' . $token;

    $config = array();
    $config = [
      'curl' => [
        CURLOPT_PROXY => EBOU_NEWS_PROXY,
      ],
    ];

    try {
      $response = \Drupal::httpClient()->request('GET', $base_url, $config);

      // If successful HTTP query.
      if ($response->getStatusCode() == 200) {
        drupal_set_message(t('Desinscription reussie'));
      }
      else {
        if ($response->getStatusCode() == 403) {
          drupal_set_message(t("Le lien de désinscription n'est pas valide. Si le probleme persiste, contactez l'assistance e-bourgogne."), 'error');
        }
        else {
          drupal_set_message(t("Une erreur est survenue lors de la désincription à la newsletter. Si le probleme persiste, contactez l'assistance e-bourgogne."), 'error');
        }
      }
    }
    catch (ClientException $e) {
      if ($e->getResponse()->getStatusCode() == 403) {
        drupal_set_message(t("Le lien de désinscription n'est pas valide. Si le probleme persiste, contactez l'assistance e-bourgogne."), 'error');
      }
      else {
        drupal_set_message(t("Une erreur est survenue lors de la désincription à la newsletter. Si le probleme persiste, contactez l'assistance e-bourgogne."), 'error');
      }
    }
    catch (RequestException $e) {
      drupal_set_message(t("Une erreur est survenue lors de la désincription à la newsletter. Si le probleme persiste, contactez l'assistance e-bourgogne."), 'error');
    }

    return new RedirectResponse(\Drupal::url('ebourgognenewsletter.site_main'));

  }

}
