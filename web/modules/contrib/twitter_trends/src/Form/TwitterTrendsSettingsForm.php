<?php

namespace Drupal\twitter_trends\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\LinkGenerator;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class TwitterTrendsSettingsForm.
 *
 * @package Drupal\twitter_trends\Form
 */
class TwitterTrendsSettingsForm extends ConfigFormBase {

  /**
   * The link generator service variable.
   *
   * @var \Drupal\Core\Utility\LinkGenerator
   */
  protected $linkGenerator;
  /**
   * The state keyvalue collection.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs LinkGenerator object.
   *
   * @param \Drupal\Core\Utility\LinkGenerator $link_generator
   *   Link Generator Service.
   * @param \Drupal\Core\State\StateInterface $state
   *   State Service Object.
   */
  public function __construct(LinkGenerator $link_generator, StateInterface $state) {
    $this->linkGenerator = $link_generator;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('link_generator'),
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'twitter_trends.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'twitter_trends_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $state = $this->state;
    $twitter_app = Url::fromUri('https://apps.twitter.com', ['attributes' => ['target' => '_blank']]);
    $app_link = $this->linkGenerator->generate($this->t('Click here'), $twitter_app);
    $form['trends'] = [
      '#type' => 'details',
      '#title' => $this->t('Twitter Trends Settings'),
      '#open' => TRUE,
      '#description' => "<p>" . $this->t('@click to register twitter application & enter the auto generated keys/secret/token in below fields.', ['@click' => $app_link]) . "</p>",
    ];
    $form['trends']['twt_consumer_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Consumer Key'),
      '#description' => $this->t('Enter the Consumer Key here'),
      '#maxlength' => 64,
      '#size' => 64,
      '#required' => TRUE,
      '#default_value' => $state->get('twt_consumer_key'),
    ];
    $form['trends']['twt_consumer_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Consumer Secret'),
      '#description' => $this->t('Enter the Consumer Secret here'),
      '#maxlength' => 64,
      '#size' => 64,
      '#required' => TRUE,
      '#default_value' => $state->get('twt_consumer_secret'),
    ];
    $form['trends']['twt_access_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Access Token'),
      '#description' => $this->t('Enter the Access Token here'),
      '#maxlength' => 64,
      '#size' => 64,
      '#required' => TRUE,
      '#default_value' => $state->get('twt_access_token'),
    ];
    $form['trends']['twt_token_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Access Token Secret'),
      '#description' => $this->t('Enter the Access Token Secret here'),
      '#maxlength' => 64,
      '#size' => 64,
      '#required' => TRUE,
      '#default_value' => $state->get('twt_token_secret'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $values = [
      'twt_consumer_key' => $form_state->getValue('twt_consumer_key'),
      'twt_consumer_secret' => $form_state->getValue('twt_consumer_secret'),
      'twt_access_token' => $form_state->getValue('twt_access_token'),
      'twt_token_secret' => $form_state->getValue('twt_token_secret'),
    ];
    $this->state->setMultiple($values);
  }

}
