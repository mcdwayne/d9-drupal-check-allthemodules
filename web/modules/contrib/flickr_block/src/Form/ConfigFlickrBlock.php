<?php

namespace Drupal\flickr_block\Form;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\flickr_block\FlickrAPI;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ConfigFlickrBlock.
 */
class ConfigFlickrBlock extends ConfigFormBase implements
    ContainerInjectionInterface {

  protected $flickrAPI;

  /**
   * Contruct method.
   *
   * @inheritDoc
   */
  public function __construct(FlickrAPI $flickrAPI) {
    $this->flickrAPI = $flickrAPI;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('flickr.block.api'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'config_flickr_block';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('flickr_block.config');

    $form['flickr_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API KEY'),
      '#description' => $this->t('Flickr Api Key. <a href="https://www.flickr.com/services/apps/create/apply" target="_blank">More info</a>'),
      '#maxlength' => 40,
      '#size' => 64,
      '#default_value' => $config->get('flickr_api_key'),
      '#required' => TRUE,
    ];
    $form['flickr_user_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('User ID'),
      '#description' => $this->t('Flickr user ID using format 12345678@N02. <a href="https://www.webpagefx.com/tools/idgettr/" target="_blank">How obtain from username?</a>'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('flickr_user_id'),
      '#required' => TRUE,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $flick = $this->flickrAPI;
    $params = $flick->generateParams([
      'flickr_api_key' => $form_state->getValue('flickr_api_key'),
      'flickr_user_id' => $form_state->getValue('flickr_user_id'),
      'flickr_number_photos' => 1,
      'flickr_photoset_id' => NULL,
    ]);

    $response = $flick->call($params);
    if (!$response) {
      $form_state->setErrorByName('',
        $this->t('An error has occurred whit the Flickr API response.'));
    }
    else {
      if (isset($response['stat']) && $response['stat'] == 'fail') {
        switch ($response['code']) {
          case 100:
            $form_state->setErrorByName('flickr_api_key',
              $response['message'] .
              ' [' . $response['code'] . ']');
            break;

          case 2:
            $form_state->setErrorByName('flickr_user_id', $response['message'] .
              ' [' . $response['code'] . ']');
            break;

          default:
            $form_state->setErrorByName('', $response['message'] .
              ' [' . $response['code'] . ']');
            break;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('flickr_block.config')
      ->set('flickr_api_key', $form_state->getValue('flickr_api_key'))
      ->set('flickr_user_id', $form_state->getValue('flickr_user_id'))
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'flickr_block.config',
    ];
  }

}
