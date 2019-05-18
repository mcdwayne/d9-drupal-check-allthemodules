<?php

namespace Drupal\qwantsearch\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class Settings.
 *
 * @package Drupal\qwantsearch\Form
 */
class SearchForm extends FormBase {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The http request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Constructs a new EmailExampleGetFormPage.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config manager.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   */
  public function __construct(ConfigFactoryInterface $configFactory, Request $request) {
    $this->configFactory = $configFactory;
    $this->request = $request;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('request_stack')->getCurrentRequest()
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'qwantsearch_search_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['search'] = [
      '#type' => 'textfield',
      '#default_value' => $this->request->get('search'),
      '#attributes' => [
        'placeholder' => $this->t('Search'),
      ],
    ];

    $form['actions'] = [
      '#type' => 'container',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('qwantsearch.search_page', ['search' => $form_state->getValue('search')]);
  }

}
