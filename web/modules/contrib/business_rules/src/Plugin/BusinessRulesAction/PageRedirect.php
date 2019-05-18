<?php

namespace Drupal\business_rules\Plugin\BusinessRulesAction;

use Drupal\business_rules\ActionInterface;
use Drupal\business_rules\Events\BusinessRulesEvent;
use Drupal\business_rules\ItemInterface;
use Drupal\business_rules\Plugin\BusinessRulesActionPlugin;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class PageRedirect.
 *
 * @package Drupal\business_rules\Plugin\BusinessRulesAction
 *
 * @BusinessRulesAction(
 *   id = "page_redirect",
 *   label = @Translation("Page redirect"),
 *   group = @Translation("System"),
 *   description = @Translation("Page redirect."),
 *   isContextDependent = FALSE,
 *   hasTargetEntity = FALSE,
 *   hasTargetBundle = FALSE,
 *   hasTargetField = FALSE,
 * )
 */
class PageRedirect extends BusinessRulesActionPlugin {

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array &$form, FormStateInterface $form_state, ItemInterface $item) {
    $settings['url'] = [
      '#type'          => 'textfield',
      '#title'         => t('Url'),
      '#required'      => TRUE,
      '#default_value' => $item->getSettings('url'),
      '#description'   => t('The full url to redirect as "http://www.example.com/page" if it is external or the relative path if it is internal as "/node/1". You can use variables to compose the url.'),
    ];

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function execute(ActionInterface $action, BusinessRulesEvent $event) {
    $url = $action->getSettings('url');
    $url = $this->processVariables($url, $event->getArgument('variables'));

    $redirect = new RedirectResponse($url);
    $redirect->send();

    $result = [
      '#type'   => 'markup',
      '#markup' => t('Page redirect to: %url.', ['%url' => $url]),
    ];

    return $result;
  }

}
