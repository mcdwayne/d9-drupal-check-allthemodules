<?php

namespace Drupal\pagarme_marketplace\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Url;
use Drupal\pagarme\Pagarme\PagarmeSdk;
use Drupal\pagarme\Helpers\PagarmeUtility;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class CompanyDetailController.
 *
 * @package Drupal\pagarme_marketplace\Controller
 */
class CompanyDetailController extends ControllerBase {
  /**
   * Drupal Routing Match.
   *
   * @var Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $route_match;
  /**
   * CompanyDetailController constructor.
   *
   * @param Drupal\Core\Routing\CurrentRouteMatch $route_match
   *   The Drupal Core Route Match Class.
   */
  public function __construct(CurrentRouteMatch $route_match) {
    $this->route_match = $route_match;
  }
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match')
    );
  }
  /**
   * Public Render Method detailRender.
   *
   * @return Return an array for markup render. Example: ['#markup' => $yourMarkup]
   */
  public function detailRender() {
    $pagarmeSdk = new PagarmeSdk($this->route_match->getParameter('company'));
    $companyInfo = $pagarmeSdk->getCompanyInfo();
    $balance = $pagarmeSdk->pagarme->balance()->get();
    $tablesMarkup = $this->renderBalanceTable($balance);
    /* Default Test Recipient ID Pagarme.me */
    $defaultTestRecipientId = $companyInfo->default_recipient_id->test;
    $this->renderTestAccountMarkup($defaultTestRecipientId, $tablesMarkup);
    /* Default Live Recipient ID Pagarme.me */
    $defaultLiveRecipientId = $companyInfo->default_recipient_id->live;
    $this->renderLiveAccountMarkup($defaultLiveRecipientId, $tablesMarkup);
    return ['#markup' => $tablesMarkup];
  } 
  /**
   * private processBalanceTable.
   * 
   * Renders The balance table markup
   *
   * @param Drupal\Core\Routing\CurrentRouteMatch $balance
   * 
   * @return string The given markup rendered
   */
  private function renderBalanceTable($balance) {
    $rowsBalanceTable = [];
    $rowsBalanceTable[] = [
      'amount_receivable' => PagarmeUtility::currencyAmountFormat($balance->getWaitingFunds()->amount, 'BRL', 'integer'),
      'available_value' => PagarmeUtility::currencyAmountFormat($balance->getAvailable()->amount, 'BRL', 'integer'),
      'amount_already_transferred' => PagarmeUtility::currencyAmountFormat($balance->getTransferred()->amount, 'BRL', 'integer'),
    ];
    $balanceTable['company_detail']['table'] = [
      '#theme' => 'table',
      '#header' => [
        'amount_receivable' => $this->t('Amount receivable'), 
        'available_value' => $this->t('Available value'),
        'amount_already_transferred' => $this->t('Amount already transferred'),
      ],
      '#rows' => $rowsBalanceTable,
    ];
    $balanceTableContainer = [
      '#theme' => 'details',
      '#attributes' => ['open' => 'true'],
      '#title' => 'Saldo',
      '#children' => $balanceTable
    ];
    return render($balanceTableContainer);
  }
  /**
   * private renderTestAccountMarkup.
   * 
   * Render test account information markup
   *
   * @param string $testRecipientId
   * @param string $tablesMarkup Reference variable
   * 
   * @return void|array
   */
  private function renderTestAccountMarkup($testRecipientId, &$tablesMarkup) {
    $pagarmeSdk = new PagarmeSdk($this->route_match->getParameter('company'));
    try {
      $testRecipient = $pagarmeSdk->pagarme->recipient()->get($testRecipientId);
    } catch (Exception $e) {
      exit;
      \Drupal::logger('pagarme')->error($e->getMessage());
    } finally {
      if (is_null($testRecipient)) {
        return ['#markup' => $tablesMarkup];
      }
    }
    /* Test Account Information Table Rows */
    $rowsAccountInformationTable = [];
    $rowsAccountInformationTable[] = [$this->t('NAME/COMPANY NAME'), $testRecipient->getBankAccount()->getLegalName()];
    $rowsAccountInformationTable[] = [$this->t('BANK'), $testRecipient->getBankAccount()->getBankCode()];
    $rowsAccountInformationTable[] = [$this->t('CPF/CNPJ'), $testRecipient->getBankAccount()->getDocumentNumber()];
    $rowsAccountInformationTable[] = [$this->t('AGENCY'), $testRecipient->getBankAccount()->getAgencia()];
    $rowsAccountInformationTable[] = [$this->t('BANK ACCOUNT'), $testRecipient->getBankAccount()->getConta()];
    $accountInformationTable['company_detail']['table'] = [
      '#theme' => 'table',
      '#rows' => $rowsAccountInformationTable,
    ];
    $accountInformationTableContainer = [
      '#theme' => 'details',
      '#attributes' => ['open' => 'true'],
      '#title' => $this->t('Account information'),
      '#children' => $accountInformationTable
    ];
    $markup = render($accountInformationTableContainer);
    $tablesMarkup .= $markup;
  }
  /**
   * private renderLiveAccountMarkup.
   * 
   * Render live account information markup
   *
   * @param string $liveRecipientId
   * @param string $tablesMarkup Reference variable
   * 
   * @return void|array
   */
  private function renderLiveAccountMarkup($liveRecipientId, &$tablesMarkup) {
    $pagarmeSdk = new PagarmeSdk($this->route_match->getParameter('company'));
    try {
      $defaultLiveRecipient = $pagarmeSdk->pagarme->recipient()->get($liveRecipientId);
    } catch (Exception $e) {
      Drupal::logger('pagarme')->error($e->getMessage());
    } finally {
      if (is_null($defaultLiveRecipient)) {
        return ['#markup' => $tablesMarkup];
      }
    }
    /* Test Account Information Table Rows */
    $rowsLiveAccountInformationTable = [];
    $rowsLiveAccountInformationTable[] = [$this->t('NAME/COMPANY NAME'), $defaultLiveRecipient->getBankAccount()->getLegalName()];
    $rowsLiveAccountInformationTable[] = [$this->t('BANK'), $defaultLiveRecipient->getBankAccount()->getBankCode()];
    $rowsLiveAccountInformationTable[] = [$this->t('CPF/CNPJ'), $defaultLiveRecipient->getBankAccount()->getDocumentNumber()];
    $rowsLiveAccountInformationTable[] = [$this->t('AGENCY'), $defaultLiveRecipient->getBankAccount()->getAgencia()];
    $rowsLiveAccountInformationTable[] = [$this->t('BANK ACCOUNT'), $defaultLiveRecipient->getBankAccount()->getConta()];
    $liveAccountInformationTable['company_detail']['table'] = [
      '#theme' => 'table',
      '#rows' => $rowsLiveAccountInformationTable,
    ];
    $liveAccountInformationTableContainer = [
      '#theme' => 'details',
      '#attributes' => ['open' => 'true'],
      '#title' => $this->t('Live Account information'),
      '#children' => $liveAccountInformationTable
    ];
    $markup = render($liveAccountInformationTableContainer);
    $tablesMarkup .= $markup;
  }
}
