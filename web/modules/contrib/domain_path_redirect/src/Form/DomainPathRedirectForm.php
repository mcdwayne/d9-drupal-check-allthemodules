<?php

namespace Drupal\domain_path_redirect\Form;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Routing\MatchingRouteNotFoundException;
use Drupal\Core\Url;
use Drupal\domain_path_redirect\Entity\DomainPathRedirect;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Provides the redirect create/edit form.
 */
class DomainPathRedirectForm extends ContentEntityForm {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The configurable language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Contains the redirect.settings configuration object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityManagerInterface $entity_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, TimeInterface $time = NULL, LanguageManagerInterface $language_manager, ConfigFactoryInterface $config) {
    parent::__construct($entity_manager, $entity_type_bundle_info, $time);
    $this->languageManager = $language_manager;
    $this->config = $config->get('redirect.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('language_manager'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareEntity() {
    /** @var \Drupal\domain_path_redirect\Entity\DomainPathRedirect $redirect */
    $redirect = $this->entity;

    if ($redirect->isNew()) {

      $source_query = [];
      if ($this->getRequest()->get('source_query')) {
        $source_query = $this->getRequest()->get('source_query');
      }

      $redirect_options = [];
      $redirect_query = [];
      if ($this->getRequest()->get('redirect_options')) {
        $redirect_options = $this->getRequest()->get('redirect_options');
        if (isset($redirect_options['query'])) {
          $redirect_query = $redirect_options['query'];
          unset($redirect_options['query']);
        }
      }

      $source_url = urldecode($this->getRequest()->get('source'));
      if (!empty($source_url)) {
        $redirect->setSource($source_url, $source_query);
      }

      $redirect_url = urldecode($this->getRequest()->get('redirect'));
      if (!empty($redirect_url)) {
        try {
          $redirect->setRedirect($redirect_url, $redirect_query, $redirect_options);
        }
        catch (MatchingRouteNotFoundException $e) {
          drupal_set_message($this->t('Invalid redirect URL %url provided.', ['%url' => $redirect_url]), 'warning');
        }
      }

      $redirect->setLanguage($this->getRequest()->get('language') ? $this->getRequest()->get('language') : Language::LANGCODE_NOT_SPECIFIED);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    /** @var \Drupal\domain_path_redirect\Entity\DomainPathRedirect $domain_path_redirect */
    $domain_path_redirect = $this->entity;

    // Add ajax callback to domain autocomplete field.
    if (isset($form['domain']['widget'][0]['target_id'])) {
      $form['#attributes']['id'] = 'domain-redirect-form';
      $form['domain']['widget'][0]['target_id']['#ajax'] = [
        'callback' => '::updatePreview',
        'event' => 'autocompleteclose',
        'wrapper' => 'domain-redirect-form',
        'method' => 'replace',
      ];
    }

    // Only add the configured languages and a single key for all languages.
    if (isset($form['language']['widget'][0]['value'])) {
      foreach ($this->languageManager->getLanguages(LanguageInterface::STATE_CONFIGURABLE) as $langcode => $language) {
        $form['language']['widget'][0]['value']['#options'][$langcode] = $language->getName();
      }
      $form['language']['widget'][0]['value']['#options'][LanguageInterface::LANGCODE_NOT_SPECIFIED] = $this->t('- All languages -');
    }

    $default_code = $domain_path_redirect->getStatusCode() ?
      $domain_path_redirect->getStatusCode() :
      $this->config->get('default_status_code');

    $form['status_code'] = [
      '#type' => 'select',
      '#title' => $this->t('Redirect status'),
      '#description' => $this->t('You can find more information about HTTP redirect status codes at <a href="@status-codes">@status-codes</a>.', ['@status-codes' => 'http://en.wikipedia.org/wiki/List_of_HTTP_status_codes#3xx_Redirection']),
      '#default_value' => $default_code,
      '#options' => redirect_status_code_options(),
    ];

    $form['redirect_source']['#attributes']['class'][] = 'domain-redirect-path';
    $form['#attached']['library'][] = 'domain_path_redirect/drupal.domain_path_redirect.admin';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $source = $form_state->getValue(['redirect_source', 0]);
    $redirect = $form_state->getValue(['redirect_redirect', 0]);

    if ($source['path'] == '<front>') {
      $form_state->setErrorByName('redirect_source', $this->t('It is not allowed to create a redirect from the front page.'));
    }
    if (strpos($source['path'], '#') !== FALSE) {
      $form_state->setErrorByName('redirect_source', $this->t('The anchor fragments are not allowed.'));
    }
    if (strpos($source['path'], '/') === 0) {
      $form_state->setErrorByName('redirect_source', $this->t('The url to redirect from should not start with a forward slash (/).'));
    }

    try {
      $source_url = Url::fromUri('internal:/' . $source['path']);
      $redirect_url = Url::fromUri($redirect['uri']);
      // It is relevant to do this comparison only in case the source path has
      // a valid route. Otherwise the validation will fail on the redirect path
      // being an invalid route.
      if ($source_url->toString() == $redirect_url->toString()) {
        $form_state->setErrorByName('redirect_redirect', $this->t('You are attempting to redirect the page to itself. This will result in an infinite loop.'));
      }
    }
    catch (\InvalidArgumentException $e) {
      // Do nothing, we want to only compare the resulting URLs.
    }

    $parsed_url = UrlHelper::parse(trim($source['path']));
    $path = isset($parsed_url['path']) ? $parsed_url['path'] : NULL;
    $query = isset($parsed_url['query']) ? $parsed_url['query'] : NULL;
    $domain = $form_state->getValue(['domain', 0, 'target_id']);
    $hash = DomainPathRedirect::generateDomainHash($path, $domain, $query, $form_state->getValue('language')[0]['value']);

    // Search for duplicate.
    $redirects = $this->entityTypeManager
      ->getStorage('domain_path_redirect')
      ->loadByProperties(['hash' => $hash]);

    if (!empty($redirects)) {
      $redirect = array_shift($redirects);
      if ($this->entity->isNew() || $redirect->id() != $this->entity->id()) {
        $form_state->setErrorByName('redirect_source', $this->t('The source path %source is already being redirected. Do you want to <a href="@edit-page">edit the existing redirect</a>?',
          [
            '%source' => $source['path'],
            '@edit-page' => $redirect->url('edit-form'),
          ]
        ));
      }
    }
  }

  /**
   * AJAX form callback.
   */
  public function updatePreview(array $form, FormStateInterface $form_state) {
    if ((!$domain_id = $form_state->getValue('domain')[0]['target_id']) ||
      (!$domain = $this->entityTypeManager->getStorage('domain')->load($domain_id))
    ) {
      return $form;
    }

    $form['redirect_source']['widget'][0]['path']['#field_prefix'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => $domain->getHostname(),
      '#attributes' => [
        'id' => 'domain-path-prefix',
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->entity->save();
    drupal_set_message($this->t('The redirect has been saved.'));
    $form_state->setRedirect('domain_path_redirect.list');
  }

}
