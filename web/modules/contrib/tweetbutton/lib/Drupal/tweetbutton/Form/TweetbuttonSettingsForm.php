<?php

/**
 * @file
 * Contains \Drupal\tweetbutton\Form\TweetbuttonSettingsForm.
 */

namespace Drupal\tweetbutton\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a configuration form for tweetbutton.
 */
class TweetbuttonSettingsForm extends ConfigFormBase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a TweetbuttonSettingsForm object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface
   *   The module handler instance to use.
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tweetbutton_settings';
  }

  /**
  * {@inheritdoc}
  */
  public function buildForm(array $form, array &$form_state) {
    $config = $this->config('tweetbutton.settings');

    $form['button'] = array(
      '#type' => 'fieldset',
      '#title' => t('Tweet button settings'),
    );
    $form['button']['tweetbutton_tweet_text'] = array(
      '#type'          => 'textfield',
      '#title'         => t('Tweet Text'),
      '#default_value' => $config->get('tweetbutton_tweet_text'),
      '#description'  => t('Tweet text to use as a default text, if no values are passed, leave this to blank to use page title as tweet text.')
    );
    $form['button']['tokens'] = array(
      '#token_types' => array('node'),
      '#theme' => 'token_tree',
    );
    $form['button']['tweetbutton_size'] = array(
      '#title' => t('Tweetbutton size'),
      '#type' => 'select',
      '#options' => array(
        'medium' => t('Medium'),
        'large' => t('Large'),
      ),
      '#default_value' => $config->get('tweetbutton_size'),
    );
    $form['button']['tweetbutton_hashtags'] = array(
      '#title' => t('Hashtags'),
      '#type' => 'textfield',
      '#default_value' => $config->get('tweetbutton_hashtags'),
      '#description' => t('Comma separated hashtags to be used in every tweet'),
    );
    $form['button']['tweetbutton_language'] = array(
      '#title' => t('Language'),
      '#description' => t('This is the language that the button will render in on your website. People will see the Tweet dialog in their selected language for Twitter.com.'),
      '#type' => 'select',
      '#options' => array(
        'en'   => t('English'),
        'fr'   => t('French'),
        'de'   => t('German'),
        'es'   => t('Spanish'),
        'ja'   => t('Japanese'),
        'auto' => t('Automatic'),
      ),
      '#default_value' => $config->get('tweetbutton_language'),
    );

    if ($this->moduleHandler->moduleExists('shorten')) {
      $services = array();
      $services[0] = t('Use t.co twitter default url shortener');
      $all_services = module_invoke_all('shorten_service');
      foreach (array_keys($all_services) as $value) {
        $services[$value] = $value;
      }

      $form['button']['tweetbutton_shorten_service'] = array(
        '#title' => t('Shorten service to use to add custom url'),
        '#type' => 'select',
        '#options' => $services,
        '#default_value' => $config->get('tweetbutton_shorten_service'),
      );
    }

    $form['button']['follow'] = array(
      '#type' => 'fieldset',
      '#title' => t('Recommend people to follow'),
    );
    $form['button']['follow']['tweetbutton_account'] = array(
      '#type' => 'textfield',
      '#title' => t('Twitter account to follow'),
      '#description' => t('This user will be @mentioned in the suggested. Will be used as default if tweetbutton fields author twitter account is not set'),
      '#default_value' => $config->get('tweetbutton_account'),
      '#id' => 'tweetbutton-account',
    );
    $form['button']['follow']['tweetbutton_rel_account_name'] = array(
      '#type' => 'textfield',
      '#title' => t('Related Account'),
      '#default_value' => $config->get('tweetbutton_rel_account_name'),
      '#description' => t('This should be site default twitter account'),
    );
    $form['button']['follow']['tweetbutton_rel_account_description'] = array(
      '#type' => 'textfield',
      '#title' => t('Related Account Description'),
      '#default_value' => $config->get('tweetbutton_rel_account_description'),
    );

    // Follow button settings.
    $form['follow_button'] = array(
      '#type' => 'fieldset',
      '#title' => t('Follow button settings'),
    );
    $form['follow_button']['tweetbutton_follow_screen_name'] = array(
      '#type'           => 'textfield',
      '#title'          => t('Screen name to follow'),
      '#default_value'  => $config->get('tweetbutton_follow_screen_name'),
      '#required'       => TRUE,
    );

    return parent::buildForm($form, $form_state);
  }

  /**
  * {@inheritdoc}
  */
  public function validateForm(array &$form, array &$form_state) {
    return parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $values = $form_state['values'];

    $this->config('tweetbutton.settings')
      ->set('tweetbutton_tweet_text', $values['tweetbutton_tweet_text'])
      ->set('tweetbutton_size', $values['tweetbutton_size'])
      ->set('tweetbutton_hashtags', $values['tweetbutton_hashtags'])
      ->set('tweetbutton_language', $values['tweetbutton_language'])
      ->set('tweetbutton_account', $values['tweetbutton_account'])
      ->set('tweetbutton_rel_account_name', $values['tweetbutton_rel_account_name'])
      ->set('tweetbutton_rel_account_description', $values['tweetbutton_rel_account_description'])
      ->set('tweetbutton_follow_screen_name', $values['tweetbutton_follow_screen_name']);

    if ($this->moduleHandler->moduleExists('shorten')) {
      $this->configFactory->get('tweetbutton.settings')
        ->set('tweetbutton_shorten_service', $values['tweetbutton_shorten_service']);
    }

    $this->config('tweetbutton.settings')
      ->save();
    parent::submitForm($form, $form_state);
  }

}
