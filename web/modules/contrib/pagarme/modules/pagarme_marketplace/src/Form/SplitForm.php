<?php

namespace Drupal\pagarme_marketplace\Form;

use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\Markup;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Url;
use Drupal\pagarme\Helpers\PagarmeCpfCnpj;
use Drupal\pagarme\Helpers\PagarmeUtility;
use Drupal\pagarme\Pagarme\PagarmeSdk;
use Drupal\pagarme_marketplace\Helpers\PagarmeMarketplaceUtility;
use PagarMe\Sdk\BankAccount\BankAccount;
use PagarMe\Sdk\Recipient\Recipient;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;


/**
 * Class SplitForm.
 *
 * @package Drupal\pagarme_marketplace\Form
 */
class SplitForm extends FormBase {

  /**
   * The database object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  protected $route_match;

  protected $entity_type_manager;

  public function __construct(Connection $database, EntityTypeManager $entity_type_manager, CurrentRouteMatch $route_match) {
    $this->database = $database;
    $this->entity_type_manager = $entity_type_manager;
    $this->route_match = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('entity_type.manager'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'split_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $op = 'add') {
    $company = $this->route_match->getParameter('company');
    $product_variation_id = $this->route_match->getParameter('product_variation_id');
    $split_id = $this->route_match->getParameter('split_id');

    $search_product_url = Url::fromRoute(
      'pagarme_marketplace.company_split_rules_search_product', 
      [
        'company' => $this->route_match->getParameter('company')
      ]
    );

    $product_variation = ProductVariation::load($product_variation_id);

    /*
     * Validação para garantir que a variação do produto realmente exista
     */
    if (!$product_variation) {
      return new RedirectResponse($search_product_url->toString());
    }

    $config = \Drupal::config('pagarme_marketplace.settings');

    // Validação para impedir o cadastro de mais de uma regra de divisão para uma mesma variação de produto caso não esteja configurado.
    if ($op == 'add' && !$config->get('multiple_split_per_product')) {
      $split = $this->database->select('pagarme_splits', 'splits')
        ->fields('splits')
        ->condition('product_variation_id', $product_variation_id)
        ->condition('company', $company)
        ->execute()->fetchObject();

      if ($split) {
        $edit_split_url = Url::fromRoute(
            'pagarme_marketplace.company_split_rules_edit', 
            [
              'company' => $this->route_match->getParameter('company'),
              'product_variation_id' => $product_variation->id(), 
              'split_id' =>  $split->split_id,
              'op' => 'edit'
            ]
        );
        $link = Link::fromTextAndUrl($this->t('click here'), $edit_split_url)->toString();
        $message = $this->t('Exists division rule for this product, to edit') . ' ' . $link;
        drupal_set_message(Markup::create(Xss::filterAdmin($message)), 'warning');
        return new RedirectResponse($search_product_url->toString());
      }
    }

    $pagarme_sdk = new PagarmeSdk($this->route_match->getParameter('company'));
    $company_info = $pagarme_sdk->getCompanyInfo();
    
    $split = $this->database->select('pagarme_splits', 'splits')
      ->fields('splits')
      ->condition('split_id', $split_id)
      ->execute()->fetchObject();


    $form['split_id'] = array(
      '#type' => 'hidden',
      '#value' => $split_id
    );

    $form['product_variation_id'] = array(
      '#type' => 'hidden',
      '#value' => $product_variation_id
    );

    $form['product_fieldset'] = array(
      '#type' => 'fieldset',
      '#title' => t('Product bound to rule'),
    );

    $items['title'] = $this->t('Title: ') . $product_variation->getTitle();
    $items['sku'] = $this->t('SKU: ') . $product_variation->getSku();
    $amount = $product_variation->getPrice()->getNumber();
    $items['amount'] = PagarmeMarketplaceUtility::currencyAmountFormat($amount);

    $form['product_fieldset']['output'] = array(
      '#theme' => 'item_list',
      '#items' => $items
    );

    $status = TRUE;
    if(isset($split->status) && empty($split->status)) {
      $status = FALSE;
    }
    $form['status'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable'),
      '#description' => t('Uncheck this option to disable the rule, in other words, the rule shall not be applied to the product division calculation'),
      '#default_value' => $status,
    );

    $form['split_type'] = array(
      '#type' => 'select',
      '#title' => t('Type of division'),
      '#description' => t('Select the division type.'),
      '#options' => array('amount' => t('In cents'), 'percentage' => t('In percent')),
      '#default_value' => (!empty($split->split_type)) 
      ? $split->split_type : ''
    );

    $query = $this->database->select('pagarme_split_rules', 'rules');
    $query->fields('rules');
    $query->addField('recipients', 'legal_name');
    $query->addExpression("CONCAT(recipients.legal_name, ' [', recipients.recipient_id, ']')", 'recipient');
    $query->leftJoin('pagarme_recipients', 'recipients', 'recipients.recipient_id = rules.recipient_id');
    $query->condition('split_id', $split_id);
    $split_rules = $query->execute();

    $rules = array();
    foreach ($split_rules as $item) {
      $rules[] = (array) $item;
    }

    $user_input = $form_state->getUserInput();
    if (!empty($user_input['split_rules'])) {
      $rules = $user_input['split_rules'];
    }

    if (empty($form_state->get('storage_rules'))) {
       $form_state->set('storage_rules', count($rules));
    }

    if (empty($form_state->get('storage_remove'))) {
      $form_state->set('storage_remove', array());
    }

    $form['default_recipient'] = array(
      '#type' => 'fieldset', 
      '#title' => t('Primary recipient'),
      '#collapsible' => TRUE, 
      '#collapsed' => FALSE,
    );

    $form['default_recipient']['info'] = array(
      '#type' => 'markup', 
      '#markup' => '<p>RAZÃO SOCIAL: ' . $company_info->name . '<p>',
    );

    $default_amount = (!empty($split->amount)) ? $split->amount : 0;
    $default_amount = PagarmeUtility::amountIntToDecimal($default_amount);
    $description = t('Value or percentage that the principal recipient will receive from the transaction. <br> Examples: <br> Amount receivable of R$ 120.15 = 120.15 <br> Amount receivable of R$ 1000.25 = 1000.25 <br> Percentage receivable from 35% = 35% Percentage receivable of 51.99% = 51.99');
    $form['default_recipient']['default_amount'] = array(
      '#type' => 'textfield',
      '#title' => t('Amount'),
      '#description' => Xss::filterAdmin($description),
      '#size' => 20,
      '#default_value' => $default_amount,
    );

    $form['default_recipient']['default_liable'] = array(
      '#type' => 'checkbox',
      '#title' => 'Chargeback',
      '#default_value' => (empty($split->liable)) ? FALSE : TRUE,
      '#description' => t("Defines whether you will be responsible for the transaction risk (chargeback)"),
    );

    $form['default_recipient']['default_charge_processing_fee'] = array(
      '#type' => 'checkbox',
      '#title' => t('Rate Pagar.me'),
      '#description' => t('Define whether you will be charged for a fee of Pagar.me'),
      '#default_value' => (empty($split->charge_processing_fee)) ? FALSE : TRUE,
    );

    $form['split_rules'] = array(
      '#type' => 'markup',
      '#markup' => '',
      '#prefix' => '<table id="rules-fieldset-wrapper">',
      '#suffix' => '</table>',
      '#tree' => TRUE,
    );

    $form['split_rules']['header'] = array(
      '#markup' => '<thead><tr><th>' . t('Recipient') . '</th><th>' . t('Amount') . '</th><th>' . t('Chargeback') . '</th><th>' . t('Rate Pagar.me') . '</th><th>' . t('Remove') . '</th></tr></thead>',
    );

    $form['split_rules']['#attached']['html_head'][] = [
      [
        '#tag' => 'style',
        '#value' => '.remove-recipient { display: none; }',
      ],
      'banner-css'
    ];
    for ($i = 0; $i < $form_state->get('storage_rules'); $i++) {
      $style = (in_array($i, $form_state->get('storage_remove'))) ? 'class="remove-recipient"' : '';
      $form['split_rules'][$i] = array(
        '#type' => 'markup',
        '#prefix' => '<tr ' . $style . '>',
        '#suffix' => '</tr>',
        '#tree' => TRUE,
      );

      $form['split_rules'][$i]['recipient'] = array(
        '#type' => 'textfield',
        '#description' => t('Recipient who will receive the value described in this rule.'),
        '#autocomplete_route_name' => 'pagarme_marketplace.recipients_autocomplete',
        '#autocomplete_route_parameters' => array('company' => $this->route_match->getParameter('company')),
        '#default_value' => (!empty($rules[$i]['recipient'])) ? $rules[$i]['recipient'] : '',
        '#prefix' => '<td>',
        '#suffix' => '</td>',
      );

      $rule_amount = PagarmeUtility::amountIntToDecimal($rules[$i]['amount']);
      $form['split_rules'][$i]['amount'] = array(
        '#type' => 'textfield',
        '#title' => t('Amount'),
        '#title_display' => 'invisible',
        '#description' => t('Value or percentage that the recipient will receive from the transaction.'),
        '#default_value' => $rule_amount,
        '#size' => 20,
        '#prefix' => '<td>',
        '#suffix' => '</td>',
      );

      $form['split_rules'][$i]['liable'] = array(
        '#type' => 'checkbox',
        '#description' => t("Defines whether the recipient will be responsible for the transaction risk (chargeback)"),
        '#default_value' => (!empty($rules[$i]['liable'])) ? TRUE : FALSE,
        '#prefix' => '<td>',
        '#suffix' => '</td>',
      );

      $form['split_rules'][$i]['charge_processing_fee'] = array(
        '#type' => 'checkbox',
        '#description' => t('Defines whether the recipient will be charged for the Pagar.me fee.'),
        '#default_value' => (!empty($rules[$i]['charge_processing_fee'])) ? TRUE : FALSE,
        '#prefix' => '<td>',
        '#suffix' => '</td>',
      );

      $form['split_rules'][$i]['remove'] = array(
        '#type' => 'checkbox',
        '#default_value' => (!empty($rules[$i]['remove'])) ? $rules[$i]['remove'] : '',
        '#prefix' => '<td>',
        '#suffix' => '</td>',
      );
    }

    $form['split_rules'][] = array(
      '#prefix' => '<tr>',
      '#suffix' => '</tr>',
      'add_split_rule' => array(
        '#type' => 'submit',
        '#value' => t('Add new rule'),
        '#ajax' => array(
          'callback' => '::splitRuleAddOneCallback',
          'effect' => 'fade',
          'wrapper' => 'rules-fieldset-wrapper',
        ),
        '#prefix' => '<td colspan="4">',
        '#suffix' => '</td>',
        '#limit_validation_errors' => array(),
        '#submit' => array('::splitRuleAddOne'),
      ),
      'line_remove' => array(
        '#type' => 'submit',
        '#value' => t('Remove'),
        '#ajax' => array(
          'callback' => '::splitRuleAddOneCallback',
          'effect' => 'fade',
          'wrapper' => 'rules-fieldset-wrapper',
        ),
        '#prefix' => '<td>',
        '#suffix' => '</td>',
        '#limit_validation_errors' => array(),
        '#submit' => array('::splitRuleRemoveOne'),
      )
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save rule'),
    );

    $form_state->setCached(FALSE);

    return $form;
  }

  /**
    * {@inheritdoc}
    */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $values =  $form_state->getValues();
    if ($values['op']->__toString() != $this->t('Save rule')) {
      return;
    }
    $total_rule = 0;

    $has_liable = FALSE;
    $has_charge_processing_fee = FALSE;
    if (!empty($values['default_liable'])) {
      $has_liable = TRUE;
    }
    if (!empty($values['default_charge_processing_fee'])) {
      $has_charge_processing_fee = TRUE;
    }

    $default_amount = PagarmeUtility::amountDecimalToInt($values['default_amount']);
    $form_state->setValue('default_amount', $default_amount);
    $total_rule += $default_amount;

    if (!empty($values['split_rules'])) {
      array_pop($values['split_rules']);
      foreach ($values['split_rules'] as $key => $rule) {
        if (in_array($key, $form_state->get('storage_remove'))) {
          continue;
        }

        // validação de recebedor
        if (empty($rule['recipient'])) {
          $message = $this->t('Recipient field is required.');
          $form_state->setErrorByName(
              'split_rules][' . $key . '][recipient', 
              $message
          );
        } 
        else {
          $matches = array();
          $result = preg_match('/\[([0-9]+)\]$/', $rule['recipient'], $matches);
          if ($result > 0) {
            $id = $matches[$result];
            $count = $this->database->select('pagarme_recipients', 'recipients')
              ->fields('recipients', array('recipient_id'))
              ->condition('recipient_id', $id)
              ->countQuery()
              ->execute()
              ->fetchAssoc();
            if ($count['expression']) {
              $rules = $form_state->getValue('split_rules');
              $rules[$key]['recipient_id'] = $id;
              $form_state->setValue('split_rules', $rules);
            } 
            else {
              $message = $this->t('Recipient with ID %id can not be found', array('%id' => $id));
              $form_state->setErrorByName(
                  'split_rules][' . $key . '][recipient', 
                  $message
              );
            }
          } 
          else {
            $message = $this->t('Informed recipient is not valid, use autocomplete to add a valid recipient');
            $form_state->setErrorByName(
                'split_rules][' . $key . '][recipient', 
                $message
            );
          }
        }

        $amount_integer = PagarmeUtility::amountDecimalToInt($rule['amount']);
        $rules = $form_state->getValue('split_rules');
        $rules[$key]['amount_integer'] = $amount_integer;
        $form_state->setValue('split_rules', $rules);
        $total_rule += $amount_integer;

        if (!empty($rule['liable'])) {
          $has_liable = TRUE;
        }
        if (!empty($rule['charge_processing_fee'])) {
          $has_charge_processing_fee = TRUE;
        }
      }
    }

    if (!$has_liable) {
      $form_state->setErrorByName('default_recipient][default_liable', $this->t('It is necessary to inform at least one recipient who will be responsible for the transaction risk (chargeback).'));
    }

    if (!$has_charge_processing_fee) {
      $form_state->setErrorByName('default_recipient][default_charge_processing_fee', $this->t('It is necessary to inform at least one recipient that will be charged for the rate of Pagar.me'));
    }

    switch ($values['split_type']) {
      case 'percentage':
        if ($total_rule !== 10000) {
          $form_state->setErrorByName('split_rules', $this->t('The sum of the split rule must be 100%'));
        }
        break;
      default:
        $product_variation = ProductVariation::load($values['product_variation_id']);

        $amount = $product_variation->getPrice()->getNumber();
        $price = PagarmeUtility::amountDecimalToInt($amount);

        if ($total_rule != $price) {
          $message = $this->t("The sum of the split rule must be equal to the value of the product") . PagarmeMarketplaceUtility::currencyAmountFormat($amount);
          $form_state->setErrorByName('split_rules', $message);
        }
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $default_amount = 0;
    if (!empty($values['default_amount'])) {
      $default_amount = $values['default_amount'];
    }

    $default_liable = 0;
    if (!empty($values['default_liable'])) {
      $default_liable = $values['default_liable'];
    }

    $default_charge_processing_fee = 0;
    if (!empty($values['default_charge_processing_fee'])) {
      $default_charge_processing_fee = $values['default_charge_processing_fee'];
    }

    $split = array(
      'split_id' => $values['split_id'],
      'product_variation_id' => $values['product_variation_id'],
      'company' => $this->route_match->getParameter('company'),
      'split_type' => $values['split_type'],
      'amount' => $default_amount,
      'liable' => $default_liable,
      'charge_processing_fee' => $default_charge_processing_fee,
      'status' => (empty($values['status'])) ?  0 : 1,
    );

    if (!empty($values['split_rules'])) {
      foreach ($values['split_rules'] as $key => $item) {
        if (empty($item['recipient_id'])) {
          continue;          
        }

        $amount = (!empty($item['amount_integer'])) ? $item['amount_integer'] : 0;
        $liable = 0;
        if (!empty($item['liable'])) {
          $liable = $item['liable'];
        }
        $charge_processing_fee = 0;
        if (!empty($item['charge_processing_fee'])) {
          $charge_processing_fee = $item['charge_processing_fee'];
        }
        $rule = array();
        $rule['split_id'] = $values['split_id'];
        $rule['recipient_id'] = $item['recipient_id'];
        $rule['amount'] = $amount;
        $rule['liable'] = $liable;
        $rule['charge_processing_fee'] = $charge_processing_fee;
        $split['rules'][] = $rule;
      }
    }

    $this->splitSave($split);
    drupal_set_message(t('Split rule saved.'));
    $form_state->setRedirect(
        'pagarme_marketplace.company_split_rules',
        [
          'company' => $this->route_match->getParameter('company')
        ]
    );
  }

  /**
   * Callback for both ajax-enabled buttons.
   */
  public static function splitRuleAddOneCallback(array $form, FormStateInterface $form_state) {
    return $form['split_rules'];
  }

  /**
   * Submit handler for the "add-one-more" button.
   *
   * Increments the max counter and causes a rebuild.
   */
  public static function splitRuleAddOne(array $form, FormStateInterface $form_state) {
    $rules = $form_state->get('storage_rules');
    $form_state->set('storage_rules', $rules + 1);
    $form_state->setRebuild();
  }

  /**
   * Submit handler for the "remove one" button.
   *
   * Decrements the max counter and causes a form rebuild.
   */
  public function splitRuleRemoveOne(array &$form, FormStateInterface $form_state) {
    $storage_remove = [];
    $user_input = $form_state->getUserInput()['split_rules'];
    foreach ($user_input as $key => $rule) {
      if (!empty($rule['remove'])) {
        $storage_remove[$key] = $key;
        $form_state->set('storage_remove', $storage_remove);
      }
    }
    $form_state->setRebuild();
  }

  /**
   * Saves changes to a split or adds a new split.
   *
   * @param $split
   *   The $split array to be saved. If $split['split_id'] is
   *   omitted, a new split will be added.
   */
  function splitSave($split) {
    $request_time = \Drupal::time()->getRequestTime();

    $split_id = NULL;
    if (isset($split['split_id'])) {
      $split_id = $split['split_id'];
      unset($split['split_id']);
    }

    $rules = array();
    if (isset($split['rules'])) {
      $rules = $split['rules'];
      unset($split['rules']);
    }

    $split['changed'] = $request_time;
    if (!$split_id) {
      $split['created'] = $request_time;
      $split_id = $this->database->insert('pagarme_splits')
        ->fields($split)
        ->execute();
    } 
    else {
      $this->database->update('pagarme_splits')
        ->fields($split)
        ->condition('split_id', $split_id)
        ->execute();

      $this->database->delete('pagarme_split_rules')
        ->condition('split_id', $split_id)
        ->execute();
    }

    if (!empty($rules)) {
      $query = $this->database->insert('pagarme_split_rules')->fields(array('split_id', 'recipient_id', 'amount', 'liable', 'charge_processing_fee'));

      foreach ($rules as $item) {
        $amount = (!empty($item['amount'])) ? $item['amount'] : 0;
        $liable = 0;
        if (!empty($item['liable'])) {
          $liable = $item['liable'];
        }
        $charge_processing_fee = 0;
        if (!empty($item['charge_processing_fee'])) {
          $charge_processing_fee = $item['charge_processing_fee'];
        }
        $rule = array();
        $rule['split_id'] = $split_id;
        $rule['recipient_id'] = $item['recipient_id'];
        $rule['amount'] = $amount;
        $rule['liable'] = $liable;
        $rule['charge_processing_fee'] = $charge_processing_fee;
        $query->values($rule);
      }
      $query->execute();
    }
  }
}
