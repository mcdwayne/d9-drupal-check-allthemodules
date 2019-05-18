<?php

namespace Drupal\concurrent_url_negotiation\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\concurrent_url_negotiation\ConcurrentUrlNegotiationConfig;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure the FULL URL language negotiation method for this site.
 */
class ConcurrentUrlNegotiationForm extends FormBase {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The concurrent URL negotiation configuration service.
   *
   * @var \Drupal\concurrent_url_negotiation\ConcurrentUrlNegotiationConfig
   */
  protected $negotiationConfig;

  /**
   * Constructs a new NegotiationFullUrlForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, LanguageManagerInterface $language_manager, ConcurrentUrlNegotiationConfig $negotiationConfig) {
    $this->languageManager = $language_manager;
    $this->negotiationConfig = $negotiationConfig;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('language_manager'),
      $container->get('concurrent_url_negotiation.config')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'language_negotiation_configure_full_url_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['concurrent_url_description'] = [
      '#type' => 'markup',
      '#markup' => '<div>' . $this->t('This negotiator plugin replaces the core URL negotiator, which affects system even if not enabled from "Detection and selection".') . '</div>' .
      '<div>' . $this->t("{domain-any} as domain can be used to match any domain. NOTE: this won't count as a distinct domain.") . '</div>' .
      '<div>' . $this->t("'|' can be used in prefix as an or operator. ex: '|en' will match no prefix or 'en', 'nl|nl-be' will match 'nl' or 'nl-be'") . '</div>',
    ];
    $form['negotiations'] = [
      '#type' => 'container',
      '#tree' => TRUE,
    ];

    $languages = $this->languageManager->getLanguages();
    $currentRequest = $this->getRequest();
    $languageUrls = $this->negotiationConfig->getNegotiations();

    foreach ($languages as $langcode => $language) {
      if (isset($languageUrls[$langcode])) {
        $domainValue = $languageUrls[$langcode]['domain'];
        $prefixValue = implode('|', $languageUrls[$langcode]['prefixes']);
      }
      else {
        $domainValue = $currentRequest->getHost();
        $prefixValue = $langcode;
      }

      $t_args = [
        '%language' => $language->getName(),
        '%langcode' => $language->getId(),
      ];
      $form['negotiations'][$langcode] = [
        '#type' => 'fieldset',
        '#tree' => TRUE,
        '#title' => $language->isDefault() ? $this->t('%language (%langcode) (Default language)', $t_args) : $this->t('%language (%langcode)', $t_args),
      ];

      $form['negotiations'][$langcode]['domain'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Domain'),
        '#default_value' => $domainValue,
        '#required' => TRUE,
        '#ajax' => [
          'callback' => [$this, 'ajaxUpdateCrossAuthPossibility'],
          'event' => 'change',
          'disable-refocus' => TRUE,
          'progress' => [
            'type' => 'throbber',
            'message' => ' ',
          ],
        ],
      ];

      $form['negotiations'][$langcode]['prefixes'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Prefixes'),
        '#default_value' => $prefixValue,
      ];
    }

    $form['cross_auth'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable cross-domain automatic authentication.'),
      '#description' => $this->t("When enabled, if a user is logged in on a domain and navigates to another, he will be logged in automatically. This feature relies on JavaScript so for users disabling JS it won't work. If strong security in need it is better left disabled."),
      '#default_value' => $this->negotiationConfig->isCrossAuthEnabled(),
      '#disabled' => !$this->negotiationConfig->isCrossAuthPossible(),
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save configuration'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $languages = $this->languageManager->getLanguages();
    $languageMatching = [];

    // Validate domain names and prefixes, and also collect matches per lang.
    foreach ($languages as $langcode => $language) {
      $domainSelector = ['negotiations', $langcode, 'domain'];
      $domain = $form_state->getValue($domainSelector);

      $prefixesSelector = ['negotiations', $langcode, 'prefixes'];
      $prefixes = $form_state->getValue(['negotiations', $langcode, 'prefixes']);
      $prefixes = explode('|', $prefixes);

      if (!$this->isDomainValid($domain)) {
        $this->setError($form_state, $domainSelector, $this->t('Invalid domain name.'));
      }

      if (!$this->arePrefixesValid($prefixes)) {
        $this->setError($form_state, $prefixesSelector, $this->t('Invalid prefixes.'));
      }

      $languageMatching[$langcode] = $this->getMatchingUrls($domain, $prefixes);
    }

    // Validate duplicate matches.
    if (count($duplicates = $this->getDuplicateUrlMatches($languageMatching))) {
      foreach ($duplicates as $firstLang => $secondLang) {
        // We can have duplicate for single language or with another.
        if ($firstLang == $secondLang) {
          $this->setError(
            $form_state,
            ['negotiations', $firstLang, 'prefixes'],
            $this->t('Duplicate prefix detected.')
          );
        }
        else {
          // Set error for both fields pointing at each other.
          $langs = [$firstLang => $secondLang, $secondLang => $firstLang];
          foreach ($langs as $errorLang => $withLang) {
            $message = $this->t('Domain and prefix must be unique. Conflict with %lang.', [
              '%lang' => $languages[$withLang]->getName(),
            ]);
            $this->setError($form_state, [
              'negotiations', $errorLang, 'prefixes',
            ], $message);
          }
        }
      }
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $negotiations = [];

    foreach ($form_state->getValue('negotiations') as $langcode => $url) {
      $negotiations[$langcode] = [
        'domain' => $url['domain'],
        'prefixes' => explode('|', $url['prefixes']),
      ];
    }

    $this->negotiationConfig->setNegotiations($negotiations);

    // Change the cross authentication state only if it is possible.
    // We set above the new configuration so it was already determined whether
    // cross authentication is possible (multiple distinct domains available).
    if ($this->negotiationConfig->isCrossAuthPossible()) {
      $this->negotiationConfig->setCrossAuthState($form_state->getValue('cross_auth') == 1);
    }

    $form_state->setRedirect('language.negotiation');
  }

  /**
   * Sets the cross authentication checkbox disabled or enabled.
   *
   * @param array $form
   *    The form.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *    Current state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *    Response that modifies the field accordingly.
   */
  public function ajaxUpdateCrossAuthPossibility(array &$form, FormStateInterface $formState) {
    $lastDomain = NULL;
    $disable = TRUE;
    $response = new AjaxResponse();

    // Determine whether we have multiple distinct domains.
    foreach ($formState->getValue('negotiations') as $negotiation) {
      // The any domain literal should not count as a distinct domain.
      if ($negotiation['domain'] == ConcurrentUrlNegotiationConfig::DOMAIN_ANY) {
        continue;
      }

      if ($lastDomain !== NULL && $negotiation['domain'] != $lastDomain) {
        $disable = FALSE;
        break;
      }

      $lastDomain = $negotiation['domain'];
    }

    if ($disable) {
      $response->addCommand(new InvokeCommand('#edit-cross-auth', 'attr', ['disabled', '1']));
      $response->addCommand(new InvokeCommand('.form-item-cross-auth', 'addClass', ['form-disabled']));
    }
    else {
      $response->addCommand(new InvokeCommand('#edit-cross-auth', 'removeAttr', ['disabled']));
      $response->addCommand(new InvokeCommand('.form-item-cross-auth', 'removeClass', ['form-disabled']));
    }

    return $response;
  }

  /**
   * Sets error on a field marked by string array.
   *
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *    The current form state.
   * @param string[] $nameArray
   *    The field accessor strings.
   * @param string $error
   *    The error to display.
   */
  protected function setError(FormStateInterface $formState, array $nameArray, $error) {
    $formState->setErrorByName(implode('][', $nameArray), $error);
  }

  /**
   * Gets all domain-prefix string concatenates.
   *
   * @param string $domain
   *    Domain name.
   * @param string[] $prefixes
   *    Prefix array.
   *
   * @return string[]
   *    The domain+prefix array.
   */
  protected function getMatchingUrls($domain, array $prefixes) {
    $matching = [];

    foreach ($prefixes as $prefix) {
      $matching[] = $domain . '.' . $prefix;
    }

    return $matching;
  }

  /**
   * Determine whether a domain name is valid.
   *
   * @param string $domain
   *    Domain name.
   *
   * @return bool
   *    Validity.
   */
  protected function isDomainValid($domain) {
    $url = 'http://' . str_replace(array('http://', 'https://'), '', $domain);
    return parse_url($url, PHP_URL_HOST) == $domain;
  }

  /**
   * Determines whether a collection of prefixes are valid.
   *
   * @param array $prefixes
   *    Prefix array.
   *
   * @return bool
   *    Validity.
   */
  protected function arePrefixesValid(array $prefixes) {
    foreach ($prefixes as $prefix) {
      $url = 'http://localhost/' . $prefix;
      list(, $p) = explode('/', parse_url($url, PHP_URL_PATH));

      if ($p != $prefix) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * Gets duplicate negotiation matches.
   *
   * @param array $matchingPerLanguage
   *    Matches for each language.
   *
   * @return array
   *    Array of {lang-code} => {lang-code} providing duplicates.
   */
  protected function getDuplicateUrlMatches(array $matchingPerLanguage) {
    $foundMatches = [];
    $duplicates = [];

    foreach ($matchingPerLanguage as $langcode => $matches) {
      foreach ($matches as $match) {
        if (array_key_exists($match, $foundMatches)) {
          $duplicates[$langcode] = $foundMatches[$match];
        }

        $foundMatches[$match] = $langcode;
      }
    }

    return $duplicates;
  }

}
