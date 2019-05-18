<?php

namespace Drupal\janrain_connect_ui\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\janrain_connect_ui\Service\JanrainConnectUiFlowExtractorService;
use Drupal\janrain_connect_ui\Service\JanrainConnectUiFormService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class for show forms.
 */
class JanrainConnectUiPages extends ConfigFormBase {

  /**
   * Janrain Connect Form Service.
   *
   * @var \Drupal\janrain_connect_ui\Service\JanrainConnectUiFormService
   */
  private $janrainConnectFormService;

  /**
   * Janrain Connect Flow extractor.
   *
   * @var \Drupal\janrain_connect_ui\Service\JanrainConnectUiFlowExtractorService
   */
  private $janrainConnectFlowExtractor;

  /**
   * The service container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    JanrainConnectUiFormService $janrain_connect_form_service,
    ContainerInterface $container,
    JanrainConnectUiFlowExtractorService $janrain_flow_extractor
  ) {
    $this->janrainConnectFormService = $janrain_connect_form_service;
    $this->container = $container;
    $this->janrainConnectFlowExtractor = $janrain_flow_extractor;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('janrain_connect_ui.form'),
        $container->get('service_container'),
        $container->get('janrain_connect_ui.flow_extractor')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'janrain_connect_ui_pages';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'janrain_connect.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('janrain_connect.settings');
    $application_id = $config->get('application_id');
    $flowjs_url = $config->get('flowjs_url');

    $forms_to_show = $this->janrainConnectFlowExtractor->getFormsData();

    if (!$forms_to_show) {
      drupal_set_message($this->t('No Janrain Forms were found in the flow. Did you perform Janrain Sync?'), 'warning');
      return [];
    }

    // Check configurations.
    if (empty($application_id) || empty($flowjs_url)) {

      drupal_set_message($this->t('Please fill Janrain settings.'), 'error');

      $url_settings = Url::fromRoute('janrain_connect.settings')->toString();

      return new RedirectResponse($url_settings);
    }

    $forms_to_show_keys = array_keys($forms_to_show);

    foreach ($forms_to_show_keys as $key) {

      $form_data = $this->janrainConnectFormService->getForm($key);

      if (!$form_data) {
        break;
      }

      $form_id = $form_data['id'];
      $fields = !empty($form_data['fields']) ? $form_data['fields'] : NULL;

      $host = Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString();

      $link = $host . 'janrain/form/' . $form_id;

      $output = '<h3>' . $this->t('ID') . '</h3>';
      $output .= '<p>' . $form_id . '</p>';

      $output .= '<h3>' . $this->t('Link') . '</h3>';
      $output .= '<p><a target="blank" href="' . $link . '">' . $link . '</a></p>';

      if (!empty($fields)) {

        $output .= '<h3>' . $this->t('Fields') . '</h3>';

        $output .= '<ul>';

        foreach ($fields as $field) {
          $label = $field['label'];
          $output .= '<li>' . $label . '</li>';
        }

        $output .= '</ul>';
      }

      $form[$form_id] = [
        '#type' => 'details',
        '#title' => $form_id,
        '#open' => TRUE,
      ];

      $form[$form_id]['details'] = [
        '#type' => 'markup',
        '#markup' => $output,
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    parent::submitForm($form, $form_state);

    $config = $this->config('janrain_connect.settings');

    $forms_as_block = $form_state->getValue('forms_as_block');

    $config->set('forms_as_block', $forms_as_block);

    $config->save();
  }

}
