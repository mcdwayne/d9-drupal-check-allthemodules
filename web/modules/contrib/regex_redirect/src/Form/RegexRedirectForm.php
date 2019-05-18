<?php

namespace Drupal\regex_redirect\Form;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Routing\MatchingRouteNotFoundException;
use Drupal\Core\Url;
use Drupal\regex_redirect\Entity\RegexRedirect;

/**
 * Class RegexRedirectForm.
 *
 * @package Drupal\regex_redirect\Form
 */
class RegexRedirectForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   * @throws MatchingRouteNotFoundException
   */
  protected function prepareEntity() {
    /** @var \Drupal\regex_redirect\Entity\RegexRedirect $redirect */
    $redirect = $this->entity;

    if ($redirect->isNew()) {
      $this->setDefaultOptions($redirect);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\regex_redirect\Entity\RegexRedirect $redirect */
    $redirect = $this->entity;

    // Retrieve the title and language fields when they have been set
    // or else use the default.
    if (!$redirect->isNew()) {
      $title = $redirect->get('title')->getString();
      $language = $redirect->get('language')->getString();
    }
    else {
      $title = '';
      $language = regex_redirect_language_code_default();
    }

    // Retrieve the parent form.
    $form = parent::form($form, $form_state);

    // Retrieve the default status code without config.
    $default_code = $redirect->getDefaultStatusCode();

    // Set a message on how to use the source and redirect fields.
    $form['regex_message'] = [
      '#type' => 'markup',
      '#markup' => $this->t('The entire source path should be a regular expression. For example: ead\/(?P<name>[0-9a-z\.]+). The redirect itself should contain the specified captured name within <> brackets.'),
      '#weight' => -12,
    ];
    // Adds a title field to identify the regex pattern.
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#description' => $this->t('The regex redirect title used to identify the url starting points.'),
      '#default_value' => $title,
      '#required' => TRUE,
      '#weight' => -10,
    ];
    // Redefine field titles, descriptions, and sizes.
    $form['regex_redirect_source']['widget'][0]['path']['#title'] = $this->t('Redirect source');
    $form['regex_redirect_source']['widget'][0]['path']['#description'] = $this->t('Enter an internal url regex path with named captures. For example: collectie\/indexen\/(?P&lt;nr&gt;[0-9a-z]+). Please be aware that certain characters such as "$" need to be escaped due to it being part of a replacement string.');
    $form['regex_redirect_source']['widget'][0]['path']['#size'] = 120;
    $form['redirect_redirect']['widget'][0]['uri']['#description'] = $this->t('Enter an internal url path with a leading slash and named capture variables between angular brackets. For example: /onderzoeken/index/&lt;nr&gt;.');
    $form['redirect_redirect']['widget'][0]['uri']['#size'] = 120;
    $form['status_code'] = [
      '#type' => 'select',
      '#title' => $this->t('Redirect status'),
      '#default_value' => $default_code,
      '#options' => regex_redirect_status_code_options(),
    ];
    // Additional langcode field with only the configured languages to set
    // the entity language.
    $form['langcode'] = [
      '#type' => 'language_select',
      '#title' => $this->t('Regex redirect language'),
      '#languages' => LanguageInterface::STATE_CONFIGURABLE,
      '#default_value' => $language,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // No validation on queries because no queries were specified as necessary
    // and easier code is better code. There will be no filtering on xss, and
    // only dangerous protocols will be stripped.
    $source = $form_state->getValue(['regex_redirect_source', 0]);
    $source_path = UrlHelper::stripDangerousProtocols($source['path']);
    $redirect = $form_state->getValue(['redirect_redirect', 0]);
    $redirect_uri = UrlHelper::stripDangerousProtocols($redirect['uri']);

    $this->setBasicFormErrors($form_state, $source_path, $redirect_uri);
    $this->setRegexErrors($form_state, $source_path, $redirect_uri);
    $this->hasDuplicates($form_state, $source_path);
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\regex_redirect\Entity\RegexRedirect $redirect */
    $redirect = $this->entity;

    // Set language field to equal the value set in the langcode field.
    $redirect->setLanguage($form['langcode']['#value']);
    // Save title field.
    $redirect->setTitle($form['title']['#value']);

    $this->entity->save();
    $this->messenger()->addMessage($this->t('The redirect has been saved.'));
    $form_state->setRedirect('regex_redirect.list');
  }

  /**
   * Set the default options on a new regex redirect entity.
   *
   * @param \Drupal\regex_redirect\Entity\RegexRedirect $redirect
   *   The regex redirect entity.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   * @throws MatchingRouteNotFoundException
   */
  protected function setDefaultOptions(RegexRedirect $redirect) {
    $source_url = urldecode($this->getRequest()->get('source'));
    if (!empty($source_url)) {
      $redirect->setSource($source_url);
    }

    if ($this->getRequest()->get('redirect_options')) {
      $redirect_options = $this->getRequest()->get('redirect_options');
      if (isset($redirect_options['query'])) {
        unset($redirect_options['query']);
      }
      $redirect_url = urldecode($this->getRequest()->get('regex_redirect'));
      if (!empty($redirect_url)) {
        try {
          $redirect->setRedirect($redirect_url, $redirect_options);
        }
        catch (MatchingRouteNotFoundException $e) {
          $this->messenger()->addWarning($this->t('Invalid regex redirect URL %url provided.', ['%url' => $redirect_url]));
        }
      }
    }

    $redirect->setLanguage($this->getRequest()->get('language') ? $this->getRequest()->get('language') : Language::LANGCODE_SITE_DEFAULT);
  }

  /**
   * Do some basic form validation.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $source_path
   *   The source path.
   * @param string $redirect_uri
   *   The uri to redirect to.
   */
  protected function setBasicFormErrors(FormStateInterface $form_state, $source_path, $redirect_uri) {
    // Set some basic form state errors.
    if ($source_path == '<front>') {
      $form_state->setErrorByName('regex_redirect_source', $this->t('It is not allowed to create a redirect from the front page.'));
    }
    if (strpos($source_path, '#') !== FALSE) {
      $form_state->setErrorByName('regex_redirect_source', $this->t('The anchor fragments are not allowed.'));
    }
    if (strpos($source_path, '/') === 0) {
      $form_state->setErrorByName('regex_redirect_source', $this->t('The url to redirect from should not start with a forward slash (/).'));
    }
    // The redirect url must be internal.
    if (strpos($redirect_uri, '/') !== 0) {
      $form_state->setErrorByName('redirect_redirect', $this->t('The url to redirect to should be internal, and thus must start with a forward slash (/).'));
    }
  }

  /**
   * Validate the regex pattern.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $source_path
   *   The source path.
   * @param string $redirect_uri
   *   The uri to redirect to.
   */
  protected function setRegexErrors(FormStateInterface $form_state, $source_path, $redirect_uri) {
    // Check whether redirect source is a valid regex pattern and whether
    // the redirect contains the named capture name in <> brackets.
    // Set the regex delimiter in code to avoid issues with leading slash.
    $delimiter = '/';
    $source_regex = $delimiter . trim($source_path) . $delimiter;
    $redirect_regex = str_replace('internal:', '', $redirect_uri);

    if (preg_match($source_regex, NULL) === FALSE) {
      $form_state->setErrorByName('regex_redirect_source', $this->t('The regex redirect source must be a regex pattern.'));
    }
    if (preg_match('/<(.*?)>/', $redirect_regex) !== 1) {
      $form_state->setErrorByName('redirect_redirect', $this->t('The regex redirect redirect must contain a named capture (value between <> brackets).'));
    }

    try {
      $source_url = Url::fromUri('internal:/' . $source_path);
      $redirect_url = Url::fromUri($redirect_uri);

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
  }

  /**
   * Check for duplicates.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $source_path
   *   The source path.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function hasDuplicates(FormStateInterface $form_state, $source_path) {
    // Generate the hash for checking duplicates.
    $hash = RegexRedirect::generateHash($source_path, $form_state->getValue('language')[0]['value']);

    // Search for duplicate.
    // EntityManagerInterface is deprecated, but this is inherited from
    // ContentEntityForm and should be fixed when that class is fixed.
    $redirects = $this->entityTypeManager->getStorage('regex_redirect')->loadByProperties(['hash' => $hash]);

    if (!empty($redirects)) {
      $redirect = array_shift($redirects);
      if ($this->entity->isNew() || $redirect->id() != $this->entity->id()) {
        $form_state->setErrorByName('regex_redirect_source', $this->t('The source path %source is already being redirected. Do you want to <a href="@edit-page">edit the existing redirect</a>?',
          [
            '%source' => $source_path,
            '@edit-page' => $redirect->url('edit-form'),
          ]
        ));
      }
    }
  }

}
