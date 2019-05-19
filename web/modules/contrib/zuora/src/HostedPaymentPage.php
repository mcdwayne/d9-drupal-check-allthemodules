<?php

namespace Drupal\zuora;

use Drupal\Core\Render\Markup;

class HostedPaymentPage extends ZuoraClientBase {

  public function getPageUri() {
    return ($this->isSandboxed()) ?
      'https://apisandbox.zuora.com/apps/PublicHostedPageLite.do' : 'https://www.zuora.com/apps/PublicHostedPageLite.do';
  }

  public function getPageSignature($page_id) {
    /** @var \Drupal\zuora\Rest\Client $rest_client */
    $rest_client = \Drupal::getContainer()->get('zuora.client.rest');
    $response = $rest_client->post('/rsa-signatures', [
      'uri' => $this->getPageUri(),
      'method' => 'POST',
      'pageId' => $page_id,
    ]);

    if ($response['success']) {
      return $response;
    }

    return NULL;
  }

  public function getRenderArray($page_id, $next_url, $prev_url, $fields = [], $submit_enabled = true, $style = 'inline') {
    $signature = $this->getPageSignature($page_id);

    $build = [];
    $library = $this->isSandboxed() ? 'payment_page.sandbox' : 'payment_page.production';
    $build['#attached']['library'][] = 'zuora/' . $library;
    $build['#attached']['drupalSettings']['zuoraPaymentPage'] = [
      'params' => [
        'tenantId' => $signature['tenantId'],
        'id' => $page_id,
        'token' => $signature['token'],
        'signature' => $signature['signature'],
        'key' => $signature['key'],
        'style' => $style,
        'submitEnabled' => $submit_enabled,
        'url' => $this->getPageUri(),
        'locale' => \Drupal::languageManager()->getCurrentLanguage()->getId(),
      ],
      'fields' => $fields,
      'nextPage' => $next_url,
      'prevPage' => $prev_url,
    ];
    $build['#cache']['contexts'][] = 'url.query_args:refid';
    $build['#cache']['contexts'][] = 'url.query_args:zuoraEc';
    $build['#cache']['contexts'][] = 'url.query_args:zuoraEm';
    $build['iframe'] = [
      '#markup' => Markup::create('<div id="zuora_payment" ></div>'),
    ];
    // If submit enabled is false, we need to provide a button that will
    // submit the iframe.
    $build['zuora_payment_page_submit'] = [
      '#type' => 'button',
      '#attributes' => [
        'class' => ['zuora-payment-submit-button']
      ],
      '#value' => t('Continue'),
      '#access' => !$submit_enabled,
    ];

    // If submit enabled is false we also need to save the signature to the
    // session so we can validate it.
    if (!$submit_enabled) {
      $session = \Drupal::service('session');
      $session->set('zuora_payment_page_signature', $signature);

      $build['zuora_payment_page_loading'] = [
        '#type' => 'inline_template',
        '#template' => '<div class="zuora-payment-loading hidden" style="text-align: center"><img src="data:image/gif;base64,{{ loader }}" alt="{{ loading_text }}" /></div>',
        '#context' => [
          'loader' => base64_encode(file_get_contents(\Drupal::root() . '/core/misc/loading.gif')),
          'loading_text' => t('Loading'),
        ],
        '#weight' => -10,
      ];
    }

    \Drupal::moduleHandler()->alter('zuora_hosted_payment_page_build', $build);

    return $build;
  }

}
