<?php

namespace Drupal\domain_lang\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\domain_lang\DomainLangHandlerInterface;
use Drupal\language\Form\NegotiationSelectedForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure the selected language negotiation method for this site.
 */
class DomainLangNegotiationSelectedForm extends NegotiationSelectedForm {

  /**
   * The domain lang handler.
   *
   * @var \Drupal\domain_lang\DomainLangHandlerInterface
   */
  protected $domainLangHandler;

  /**
   * Language negotiation config name for current active domain.
   *
   * @var string
   */
  protected $languageNegotiationConfig;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\domain_lang\DomainLangHandlerInterface $domain_lang_handler
   *   The domain lang handler.
   */
  public function __construct(ConfigFactoryInterface $config_factory, DomainLangHandlerInterface $domain_lang_handler) {
    parent::__construct($config_factory);
    $this->domainLangHandler = $domain_lang_handler;
    $this->languageNegotiationConfig = $this->domainLangHandler->getDomainConfigName('language.negotiation');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('domain_lang.handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['language.negotiation', $this->languageNegotiationConfig];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config($this->languageNegotiationConfig);
    $form = parent::buildForm($form, $form_state);

    // Fill with initial values on first page visit.
    if (!$config->get('selected_langcode')) {
      $config->set('selected_langcode', $this->config('language.negotiation')->get('selected_langcode'));
    }

    if (isset($form['selected_langcode'])) {
      $form['selected_langcode']['#default_value'] = $config->get('selected_langcode');
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config($this->languageNegotiationConfig)
      ->set('selected_langcode', $form_state->getValue('selected_langcode'))
      ->save();

    $form_state->disableRedirect();
    drupal_set_message($this->t('The configuration options have been saved.'));
  }

}
