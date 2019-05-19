<?php

namespace Drupal\trending_images\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Provides route responses for the event info page.
 */
class SocialNetworkController extends ControllerBase {

  public function authenticationProcess($social_channel){
    $config = \Drupal::configFactory()->getEditable('trending_images.config');
    if(!empty(\Drupal::request()->get('code'))){
      // TODO: REPLACE config api key and secret with nore general solution
      $parameters['form_params'] = array(

        'client_id' => $config->get('instagram_channel_api_key'),
        'client_secret' => $config->get('instagram_channel_api_secret'),
        'grant_type' => 'authorization_code',
        'code' => \Drupal::request()->get('code'),
        'redirect_uri' => \Drupal::request()->getSchemeAndHttpHost().'/trending-images/'.$social_channel.'/authentication'
      );

      try {
        $accessTokenAPI = 'https://api.instagram.com/oauth/access_token';
        $request = \Drupal::httpClient()->request('POST', $accessTokenAPI, $parameters);
        $dataArray = json_decode($request->getBody());
        $accessToken = $dataArray->access_token;
        $config->set('instagram_authentication_token', $accessToken)
          ->save();
        $success = TRUE;
      }
      catch (\InvalidArgumentException $e) {
        $success = FALSE;
      }
      catch (\RuntimeException $e) {
        $success = FALSE;
      }

      if ($success) {
        drupal_set_message($this->t('Your site have been connected with Instagram.'));
        return [
          'text' => [
            '#markup' => $this->t('This window will be closed automatically in a few moments.'),
            '#attached' => ['library' => ['trending_images/oauth-callback']],
            '#theme_wrappers' => ['container'],
            '#attributes' => ['class' => ['trending-images-oauth-callback']],
          ],
        ];
      }
    }

    drupal_set_message($this->t('Seems like something did not go as expected and we could not connect with your Instagram API client.'), 'error');
    return $this->redirect('trending_images.configuration');
  }
}
