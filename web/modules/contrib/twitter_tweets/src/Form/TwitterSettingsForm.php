<?php
namespace Drupal\twitter_tweets\Form;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\MapArray;


class TwitterSettingsForm extends ConfigFormBase {
	
    /**
    *array An array of configuration object names that are editable
	*/
   protected function getEditableConfigNames() {
   return ['twitter_tweets.credentials'];
  }
  
   public function getFormID() {
    return 'twitter_settings_form';
  }
  
  public function buildForm(array $form, FormStateInterface $form_state) {
	  
    $config = $this->config('twitter_tweets.credentials');  //since we are extending ConfigFormBase instaad of FormBase, it gives use access to the config object
    $form['oauth_access_token'] = array(
      '#type' => 'textfield',
      '#description' => t('Oauth Access Token'),
      '#title' => t('Oauth Access Token'),
      '#default_value' => $config->get('oauth_access_token'),
    );
    $form['oauth_access_token_secret'] = array(
      '#type' => 'textfield',
      '#description' => t('Oauth Access Token Secret'),
      '#title' => t('Oauth Access Token Secret'),
      '#default_value' => $config->get('oauth_access_token_secret'),
    );
    $form['consumer_key'] = array(
      '#type' => 'textfield',
      '#description' => t('Consumer Key'),
      '#title' => t('Consumer Key'),
      '#default_value' => $config->get('consumer_key'),
    );
    $form['consumer_secret'] = array(
      '#type' => 'textfield',
      '#description' => t('Consumer Secret'),
      '#title' => t('Consumer Secret'),
      '#default_value' => $config->get('consumer_secret'),
    );
    $form['screen_name'] = array(
      '#type' => 'textfield',
      '#description' => t('Screen Name'),
      '#title' => t('Screen Name'),
      '#default_value' => $config->get('screen_name'),
    );
    $form['tweet_count'] = array(
      '#type' => 'textfield',
      '#description' => t('Tweet Count'),
      '#title' => t('Tweet Count'),
      '#default_value' => $config->get('tweet_count'),
    );
    return parent::buildForm($form,$form_state);
  }

  /**
   * Form submission handler.
   *
   *  $form -> An associative array containing the structure of the form.
   *  $form_state -> An associative array containing the current state of the form.
   */
   
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('twitter_tweets.credentials')
      ->set('oauth_access_token', $form_state->getValue('oauth_access_token'))
      ->set('oauth_access_token_secret', $form_state->getValue('oauth_access_token_secret'))
      ->set('consumer_key', $form_state->getValue('consumer_key'))
      ->set('consumer_secret', $form_state->getValue('consumer_secret'))
      ->set('screen_name', $form_state->getValue('screen_name'))
      ->set('tweet_count', $form_state->getValue('tweet_count'))
      ->save();
  }
}