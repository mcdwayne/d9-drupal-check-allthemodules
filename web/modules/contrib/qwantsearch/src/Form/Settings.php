<?php

namespace Drupal\qwantsearch\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\qwantsearch\Service\QwantSearchInterface;

/**
 * Class Settings.
 *
 * @package Drupal\drupalcampfr_social\Form
 */
class Settings extends ConfigFormBase {

  /**
   * The error message in case of invalid account.
   */
  const QWANTSEARCH_INVALID_PARTNER_ERROR = 'Invalid account';

  /**
   * The error message in case of token error.
   */
  const QWANTSEARCH_INVALID_TOKEN_ERROR = 'Token (!token) is invalid';

  /**
   * Qwant search service.
   *
   * @var \Drupal\qwantsearch\Service\QwantSearchInterface
   */
  protected $qwantSearch;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal route builder.
   *
   * @var \Drupal\Core\Routing\RouteBuilderInterface
   */
  protected $routeBuilder;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, QwantSearchInterface $qwantSearch, RouteBuilderInterface $routeBuilder) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entity_type_manager;
    $this->qwantSearch = $qwantSearch;
    $this->routeBuilder = $routeBuilder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('qwantsearch.qwantsearch'),
      $container->get('router.builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'qwantsearch.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'qwantsearch_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('qwantsearch.settings');

    $form['connection_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Connection settings'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#weight' => 0,
    ];

    $form['connection_settings']['qwantsearch_partner_id'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => 'Qwant partner ID',
      '#default_value' => $config->get('qwantsearch_partner_id'),
    ];

    $form['connection_settings']['qwantsearch_http_token'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => 'Qwant HTTP token',
      '#default_value' => $config->get('qwantsearch_http_token'),
    ];

    $form['results_page'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Results page'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#weight' => 10,
    ];

    $form['results_page']['qwantsearch_search_page'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search page url'),
      '#description' => $this->t('Enter an unused path or it may be overridden by another module. Example: my-search/page'),
      '#default_value' => $config->get('qwantsearch_search_page'),
    ];

    $form['results_page']['qwantsearch_search_page_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search page title'),
      '#default_value' => $config->get('qwantsearch_search_page_title'),
    ];

    $form['results_page']['qwantsearch_nb_items_displayed'] = [
      '#type' => 'number',
      '#title' => $this->t('Nb items displayed'),
      '#default_value' => $config->get('qwantsearch_nb_items_displayed'),
      '#min' => 1,
      '#max' => 100,
      '#step' => 1,
    ];

    $form['results_page']['qwantsearch_result_image_style'] = [
      '#type' => 'select',
      '#options' => $this->getImagesStylesSelect(),
      '#title' => $this->t('Results image style'),
      '#default_value' => $config->get('qwantsearch_result_image_style'),
    ];

    $form['results_page']['qwantsearch_no_result'] = [
      '#type' => 'textfield',
      '#title' => $this->t('No result content'),
      '#default_value' => $config->get('qwantsearch_no_result'),
      '#size' => 255,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $data = $this->qwantSearch->makeQuery();

    if ($data->status == 'error') {
      if (!empty($data->error)) {
        if ($data->error == $this::QWANTSEARCH_INVALID_PARTNER_ERROR) {
          $form_state->setErrorByName('qwantsearch_partner_id', $this->t('The partner ID does not exist on Qwant. Please check it and try again.'));
        }

        $token_error = str_replace('!token', $form_state->getValue('qwantsearch_http_token'), $this::QWANTSEARCH_INVALID_TOKEN_ERROR);
        if (strcmp($token_error, $data->error) === 0) {
          $form_state->setErrorByName('qwantsearch_http_token', $this->t('Your token seems invalid. Please check it and try again.'));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('qwantsearch.settings')
      ->set('qwantsearch_partner_id', $form_state->getValue('qwantsearch_partner_id'))
      ->set('qwantsearch_http_token', $form_state->getValue('qwantsearch_http_token'))
      ->set('qwantsearch_search_page', $form_state->getValue('qwantsearch_search_page'))
      ->set('qwantsearch_search_page_title', $form_state->getValue('qwantsearch_search_page_title'))
      ->set('qwantsearch_nb_items_displayed', $form_state->getValue('qwantsearch_nb_items_displayed'))
      ->set('qwantsearch_result_image_style', $form_state->getValue('qwantsearch_result_image_style'))
      ->set('qwantsearch_no_result', $form_state->getValue('qwantsearch_no_result'))
      ->save();

    $this->routeBuilder->rebuild();
  }

  /**
   * Returns a selection options array of image styles.
   *
   * @return array
   *   Selection options of existing image styles.
   */
  public function getImagesStylesSelect() {
    $image_styles_entities = $this->entityTypeManager->getStorage('image_style')->loadMultiple();

    $image_styles = [];
    foreach ($image_styles_entities as $image_styles_entity) {
      // Get the info we seek from the image style entity.
      $image_styles[$image_styles_entity->get('name')] = $image_styles_entity->get('label');
    }

    return $image_styles;
  }

}
