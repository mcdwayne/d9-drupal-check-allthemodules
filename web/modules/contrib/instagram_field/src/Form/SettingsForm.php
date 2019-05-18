<?php

namespace Drupal\instagram_field\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Defines a form to configure module settings for instagram field.
 */
class SettingsForm extends ConfigFormBase {
  /**
   * The variable containing the request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Dependency injection through the constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack service.
   */
  public function __construct(RequestStack $requestStack
  ) {
    $this->requestStack = $requestStack->getCurrentRequest();
  }

  /**
   * Dependency injection create.
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('request_stack'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'instagram_field.settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['config.instagram_field'];
  }

  /**
   * Settings form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    global $base_url;
    $config = $this->config('config.instagram_field');
    $form['#attached']['library'][] =
      'instagram_field/instagram_field-scripts';

    $form['callbackurl'] = [
      '#type' => 'item',
      '#title' => $this->t('Redirect URI @url', ['@url' => '(https://www.instagram.com/developer/clients/manage/)']),
      '#description' => $base_url . '/_instagram_field_callback',
    ];
    $form['clientid'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Instagram client ID'),
      '#default_value' => $config->get('clientid'),
    ];
    $form['clientsecret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Instagram client secret'),
      '#default_value' => $config->get('clientsecret'),
    ];
    $form['auth'] = [
      '#type' => 'button',
      '#value' => $this->t('Authenticate'),
      '#ajax' => [],
    ];

    $form['accesstoken'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Instagram access token'),
      '#default_value' => $config->get('accesstoken'),
    ];

    $form['imageresolution'] = [
      '#type' => 'select',
      '#title' => $this->t('Image resolution'),
      '#default_value' => $config->get('imageresolution'),
      '#options' => [
        'thumbnail' => 'Thumbnail (150x150)',
        'low_resolution' => 'Low (320x320)',
        'standard_resolution' => 'Standard (640x640)',
      ],
    ];
    $form['cachetime'] = [
      '#type' => 'number',
      '#title' => $this->t('Cache time in minutes'),
      '#default_value' => $config->get('cachetime'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * Settings form validation.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Prevent from most common error (spaces before or behind)
    $form_state->setValue('clientid', trim($form_state->getValue('clientid')));
    $form_state->setValue('clientsecret', trim($form_state->getValue('clientsecret')));
    $form_state->setValue('accesstoken', trim($form_state->getValue('accesstoken')));

    if (preg_match('/[^A-Fa-f0-9]/', $form_state->getValue('clientid'))) {
      $form_state->setErrorByName('clientid',
        $this->t('Instagram client ID contains not valid chars [A-Fa-f0-9].'));
    }
    if (preg_match('/[^A-Fa-f0-9]/', $form_state->getValue('clientsecret'))) {
      $form_state->setErrorByName('clientsecret',
        $this->t('Instagram client secret  contains not valid chars [A-Fa-f0-9].'));
    }
    if (preg_match('/[^A-Fa-f0-9.]/', $form_state->getValue('accesstoken'))) {
      $form_state->setErrorByName('accesstoken',
        $this->t('Instagram access token contains not valid chars [A-Fa-f0-9.].'));
    }
  }

  /**
   * Settings form submit.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('config.instagram_field');

    $config->set('clientid', $form_state->getValue('clientid'));
    $config->set('clientsecret', $form_state->getValue('clientsecret'));
    $config->set('accesstoken', $form_state->getValue('accesstoken'));

    $config->set('imageresolution', $form_state->getValue('imageresolution'));
    $config->set('cachetime', $form_state->getValue('cachetime'));

    $config->save();

    return parent::submitForm($form, $form_state);
  }

}
