<?php

namespace Drupal\blockchain\Form;

use Drupal\blockchain\Entity\BlockchainNodeInterface;
use Drupal\blockchain\Service\BlockchainServiceInterface;
use Drupal\blockchain\Utils\BlockchainRequestInterface;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class BlockchainSubscribeForm.
 *
 * @package Drupal\blockchain\Form
 */
class BlockchainSubscribeForm extends FormBase {

  /**
   * Blockchain service.
   *
   * @var \Drupal\blockchain\Service\BlockchainServiceInterface
   */
  protected $blockchainService;

  /**
   * Blockchain config id context.
   *
   * @var string
   */
  protected $blockchainConfigId;

  /**
   * BlockchainDashboardForm constructor.
   *
   * @param \Drupal\blockchain\Service\BlockchainServiceInterface $blockchainService
   *   Blockchain service.
   */
  public function __construct(BlockchainServiceInterface $blockchainService) {

    $this->blockchainService = $blockchainService;
    $this->blockchainConfigId = $this->getRequest()
      ->attributes
      ->get('blockchain_config');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {

    return new static(
      $container->get('blockchain.service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {

    return 'blockchain_subscribe_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['attention'] = [
      '#type' => 'item',
      '#title' => $this->t('Attention:'),
      '#markup' => $this->t('
        - blockchain entity type should be same as on endpoint;<br />
        - settings should be same as on endpoint;<br />
        - required auth settings should be set;<br />
        - blocks storage should be empty;
        '),
    ];
    $form['subscribe_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subscription endpoint'),
      '#description' => $this->t('Enter valid endpoint for subscription.'),
      '#placeholder' => 'http[s]://host|ip[:port]',
      '#required' => TRUE,
    ];
    $form['self_url'] = [
      '#type' => 'textfield',
      '#placeholder' => 'http[s]://host|ip[:port]',
      '#title' => $this->t('Self endpoint'),
      '#description' => $this->t('Enter endpoint address of this node, else this will be defined from request.'),
    ];
    $form['actions'] = [
      '#type' => 'submit',
      '#value' => $this->t('Subscribe'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $formState) {

    $url = trim($formState->getValue('subscribe_url'));
    $this->blockchainService->getConfigService()->setCurrentConfig($this->blockchainConfigId);
    if ($selfUrl = trim($formState->getValue('self_url'))) {
      $params = [
        BlockchainRequestInterface::PARAM_SELF_URL => $selfUrl,
      ];
    }
    else {
      $params = [];
    }
    $result = $this->blockchainService->getApiService()->executeSubscribe($url, $params);
    $details = $result->getStatusCode() . '|' . $result->getMessageParam() . '|' . $result->getDetailsParam();
    if ($result->isStatusOk()) {
      $this->blockchainService->getNodeService()->create(
        $this->blockchainConfigId,
        $result->getSelfParam(),
        BlockchainNodeInterface::ADDRESS_SOURCE_CLIENT,
        $url
      );
      $this->getLogger($this->getFormId())->info($details);
    }
    else {
      $this->getLogger($this->getFormId())->error($details);
    }
    $this->messenger()->addStatus($this->t('Subscription result: @details', [
      '@details' => $details,
    ]));
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $formState) {

    parent::validateForm($form, $formState);
    if ($subscribeUri = $formState->getValue('subscribe_url')) {
      if (!UrlHelper::isValid($subscribeUri, TRUE)) {
        $formState->setErrorByName('subscribe_url', $this->t('Url is not valid'));
      }
    }
    if ($selfUri = $formState->getValue('self_url')) {
      if (!UrlHelper::isValid($selfUri, TRUE)) {
        $formState->setErrorByName('self_url', $this->t('Url is not valid'));
      }
    }
    $this->blockchainService->getConfigService()->setCurrentConfig($this->blockchainConfigId);
    if ($this->blockchainService->getStorageService()->anyBlock()) {
      $formState->setError($form, $this->t('Block storage is not empty'));
    }
  }

}
