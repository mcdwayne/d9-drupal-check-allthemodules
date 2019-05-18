<?php

namespace Drupal\domain_lang\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\domain_lang\DomainLangHandlerInterface;
use Drupal\language\Form\NegotiationBrowserDeleteForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds a confirmation form to delete a browser language negotiation mapping.
 */
class DomainLangNegotiationBrowserDeleteForm extends NegotiationBrowserDeleteForm {

  /**
   * The domain lang handler.
   *
   * @var \Drupal\domain_lang\DomainLangHandlerInterface
   */
  protected $domainLangHandler;

  /**
   * Language mappings config name for current active domain.
   *
   * @var string
   */
  protected $languageMappingsConfig;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\domain_lang\DomainLangHandlerInterface $domain_lang_handler
   *   The domain lang handler.
   */
  public function __construct(DomainLangHandlerInterface $domain_lang_handler) {
    $this->domainLangHandler = $domain_lang_handler;
    $this->languageMappingsConfig = $this->domainLangHandler->getDomainConfigName('language.mappings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('domain_lang.handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [$this->languageMappingsConfig];
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url(
      'domain_lang.negotiation_browser',
      ['domain' => $this->domainLangHandler->getDomainFromUrl()->id()]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config($this->languageMappingsConfig)
      ->clear('map.' . $this->browserLangcode)
      ->save();

    $args = ['%browser' => $this->browserLangcode];
    $this->logger('language')
      ->notice('The browser language detection mapping for the %browser browser language code has been deleted.', $args);

    $form_state->setRedirect(
      'domain_lang.negotiation_browser',
      ['domain' => $this->domainLangHandler->getDomainFromUrl()->id()]
    );
    drupal_set_message($this->t('The mapping for the %browser browser language code has been deleted.', $args));
  }

}
