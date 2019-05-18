<?php

namespace Drupal\revue\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\revue\RevueApiException;
use Drupal\revue\RevueApiInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class Subscription.
 *
 * @package Drupal\revue\Form
 */
class Subscription extends FormBase {

  /**
   * The Revue config.
   *
   * @var string
   */
  protected $revueConfig;

  /**
   * The Revue API service.
   *
   * @var \Drupal\revue\RevueApiInterface
   */
  protected $revueApi;

  /**
   * {@inheritdoc}
   */
  public function __construct(RevueApiInterface $revue_api) {
    $this->revueApi = $revue_api;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('revue.revue_api')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'revue_subscription';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $revue_config = []) {
    if (empty($revue_config)) {
      $message = $this->t('Revue configuration missing. Please configure the block settings!');
      $this->logger('revue')->error($message);
      $this->messenger()->addError($message);
      return [];
    }
    $this->revueConfig = $revue_config;

    $form['#prefix'] = '<div id="subscription-form">';
    $form['#suffix'] = '</div>';

    if (!$form_state->getValue('subscription_success')) {
      $form['email'] = [
        '#type' => 'email',
        '#title' => $this->t('Email address'),
        '#required' => TRUE,
      ];

      if ($this->revueConfig['optional_fields']['first_name']) {
        $form['first_name'] = [
          '#type' => 'textfield',
          '#title' => $this->t('First name'),
        ];
      }

      if ($this->revueConfig['optional_fields']['last_name']) {
        $form['last_name'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Last name'),
        ];
      }

      $form['actions'] = [
        '#type' => 'actions',
        'submit' => [
          '#type' => 'submit',
          '#value' => $this->t('Subscribe'),
          '#ajax' => [
            'wrapper' => 'subscription-form',
            'callback' => [$this, 'ajaxSubmitHandler'],
            'method' => 'replace',
          ],
        ],
      ];
    }
    else {
      if ($this->revueConfig['old_issues_link']) {
        try {
          $form['old_issues_link'] = [
            '#markup' => $this->t('You can find older issues of the newsletter on <a href="@url">our profile page at Revue</a>.', [
              '@url' => $this->revueApi->getProfileUrl($this->revueConfig['api_key']),
            ]),
          ];
        }
        catch (RevueApiException $e) {
          $this->logger('revue')->error($e->getMessage());
        }
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    try {
      $this->revueApi->subscribe($this->revueConfig['api_key'], $form_state->getValue('email'), $form_state->getValue('first_name'), $form_state->getValue('last_name'));
      $form_state->setRebuild(TRUE);
      $form_state->setValue('subscription_success', TRUE);
      $this->messenger()->addStatus($this->t('You have successfully subscribed to the newsletter. Please check your email to confirm the subscription.'));
    }
    catch (RevueApiException $e) {
      $this->logger('revue')->error($e->getMessage());
      $this->messenger()->addError($e->getMessage());
    }
  }

  /**
   * The AJAX submit handler callback.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return array
   *   The form array.
   */
  public function ajaxSubmitHandler(array &$form, FormStateInterface $form_state) {
    return $form;
  }

}
