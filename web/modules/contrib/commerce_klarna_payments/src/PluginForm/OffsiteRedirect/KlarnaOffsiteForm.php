<?php

declare(strict_types = 1);

namespace Drupal\commerce_klarna_payments\PluginForm\OffsiteRedirect;

use Drupal\commerce_klarna_payments\Plugin\Commerce\PaymentGateway\Klarna;
use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Render\Element;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Exception;
use Klarna\Rest\Transport\Exception\ConnectorException;

/**
 * Provides the Klarna payments form.
 */
final class KlarnaOffsiteForm extends PaymentOffsiteForm {

  use MessengerTrait;
  use DependencySerializationTrait;
  use StringTranslationTrait;

  /**
   * The payment.
   *
   * @var \Drupal\commerce_payment\Entity\PaymentInterface
   */
  protected $entity;

  /**
   * Gets the payment plugin.
   *
   * @return \Drupal\commerce_klarna_payments\Plugin\Commerce\PaymentGateway\Klarna
   *   The payment plugin.
   */
  private function getPaymentPlugin() : Klarna {
    return $this->entity->getPaymentGateway()->getPlugin();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $plugin = $this->getPaymentPlugin();

    $form = parent::buildConfigurationForm($form, $form_state);

    if (!$order = $this->entity->getOrder()) {
      $this->messenger()->addError(
        $this->t('The provided payment has no order referenced. Please contact store administration if the problem persists.')
      );

      return $form;
    }
    $form = $this->buildRedirectForm($form, $form_state, $plugin->getReturnUri($order, 'commerce_klarna_payments.redirect'));

    try {
      $request = $plugin->getKlarnaConnector()->sessionRequest($order);
      $data = $plugin->getKlarnaConnector()
        ->buildTransaction($request, $order);

      $form['payment_methods'] = [
        '#theme' => 'commerce_klarna_payments_container',
        '#attached' => [
          'library' => ['commerce_klarna_payments/klarna-js-sdk'],
          'drupalSettings' => [
            'klarnaPayments' => $data,
          ],
        ],
      ];

      $form['klarna_authorization_token'] = [
        '#type' => 'hidden',
        '#value' => '',
        '#attributes' => ['data-klarna-selector' => 'authorization-token'],
      ];

      if (empty($data['payment_method_categories'])) {
        $this->messenger()->addError(
          $this->t('No payment method categories found. Usually this means that Klarna is not supported in the given country. Please contact store administration if you think this is an error.')
        );
        // Trigger a form error so we can disable continue button.
        $form_state->setErrorByName('klarna_authorization_token');
      }

      return $form;
    }
    catch (ConnectorException $e) {
      // Session initialization failed.
      $this->messenger()->addError(
        $this->t('Failed to populate Klarna order. Please contact store administration if the problem persists.')
      );
      $plugin->getLogger()->error(
        $this->t('Failed to populate Klara order #@id: @message', [
          '@id' => $order->id(),
          '@message' => $e->getMessage(),
        ])
      );

      return $form;
    }
    catch (Exception $e) {
      $this->messenger()->addError(
        $this->t('An unknown error occurred. Please contact store administration if the problem persists.')
      );

      $plugin->getLogger()->error(
        $this->t('An error occurred for order #@id: @message', [
          '@id' => $order->id(),
          '@message' => $e->getMessage(),
        ])
      );

      return $form;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildRedirectForm(array $form, FormStateInterface $form_state, $redirect_url, array $data = [], $redirect_method = self::REDIRECT_GET) {

    $form['commerce_message'] = [
      '#weight' => -10,
      '#process' => [
        [get_class($this), 'processRedirectForm'],
      ],
      '#action' => $redirect_url,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public static function processRedirectForm(array $element, FormStateInterface $form_state, array &$complete_form) {
    $element = parent::processRedirectForm($element, $form_state, $complete_form);

    foreach (Element::children($complete_form['actions']) as $name) {
      if ($complete_form['actions'][$name]['#type'] !== 'submit') {
        continue;
      }
      if ($form_state->getErrors()) {
        // Disable continue button if form has errors.
        $complete_form['actions'][$name]['#attributes']['disabled'] = 'disabled';
      }
      // Append appropriate selector so we can disable the submit button until
      // we have authorization token.
      $complete_form['actions'][$name]['#attributes']['data-klarna-selector'] = 'submit';
      $complete_form['actions'][$name]['#value'] = t('Continue');
    }
    return $element;
  }

}
