<?php

namespace Drupal\pagarme_marketplace\Form;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\Markup;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SearchProductForm.
 *
 * @package Drupal\pagarme_marketplace\Form
 */
class SearchProductForm extends FormBase {

  /**
   * The database object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  protected $entity_type_manager;

  protected $route_match;

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
    return 'search_product_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['product'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Product'),
      '#attributes' => [
        'class' => ['container-inline'],
      ],
      '#placeholder' => $this->t('Search by product title'),
      '#target_type' => 'commerce_product',
      '#required' => TRUE,
      '#selection_settings' => [
        'match_operator' => 'CONTAINS',
      ],
      '#description' => $this->t('Use this field to find the product and variation.'),
      '#ajax' => [
        'callback' => [$this, 'product_ajax_callback'],
        'effect' => 'fade',
        'event' =>'autocompleteclose',
      ],
    ];

    $options = [];
    $values = $form_state->getValues();
    if (!empty($values['product'])) {
      $product_variations = $this->entity_type_manager->getStorage('commerce_product_variation')->loadByProperties(['product_id' => [$values['product']]]);
      foreach ($product_variations as $variation) {
        $options[$variation->id()] = $variation->getSku();
      }
    }
    $form['product_variation'] = [
      '#type' => 'select',
      '#title' =>  $this->t('Product variation'),
      '#description' => $this->t('Select the variation you want to create a split rule.'),
      '#options' => $options,
      '#prefix' => '<div id="product-variation-options-replace">',
      '#suffix' => '</div>',
      '#required' => TRUE,
    ];

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Continue'),
    );

    return $form;
  }

  /**
    * {@inheritdoc}
    */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $values =  $form_state->getValues();
    $company = $this->route_match->getParameter('company');

    if ($values['product_variation']) {
      $product_variation = $this->entity_type_manager
        ->getStorage('commerce_product_variation')
        ->load($values['product_variation']);

      if ($product_variation) {
        $config = \Drupal::config('pagarme_marketplace.settings');

        // Validação para impedir o cadastro de mais de uma regra de divisão para um mesmo produto caso não esteja configurado.
        if (!$config->get('multiple_split_per_product')) {
          $result = $this->database->select('pagarme_splits')
            ->fields('pagarme_splits')
            ->condition('product_variation_id', $product_variation->id())
            ->condition('company', $company)
            ->execute();

          if ($split = $result->fetchObject()) {
            $url = Url::fromRoute(
                'pagarme_marketplace.company_split_rules_edit', 
                [
                  'company' => $this->route_match->getParameter('company'),
                  'product_variation_id' => $product_variation->id(), 
                  'split_id' =>  $split->split_id,
                  'op' => 'edit'
                ]
            );
            $link = Link::fromTextAndUrl($this->t('click here'), $url)->toString();
            $message = $this->t('Exists division rule for this product, to edit') . ' ' . $link;
            $message = Markup::create(Xss::filterAdmin($message));
            $form_state->setErrorByName('product_variation', $message);
          }
        }
      } 
      else {
        $form_state->setErrorByName('product_variation', $this->t('Invalid product, use the autocomplete to add an valid product.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $form_state->setRedirect(
        'pagarme_marketplace.company_split_rules_add',
        [
            'company' => $this->route_match->getParameter('company'),
            'product_variation_id' => $values['product_variation']
        ]
    );
  }

  /**
   * Ajax callback.
   */
  public static function product_ajax_callback(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand(
        '#product-variation-options-replace',
        render($form['product_variation'])
    ));
    return $response;
  }
}
