<?php

namespace Drupal\altruja\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\altruja\AltrujaAPI;

/**
 * Form handler for the altruja block add and edit forms.
 */
class AltrujaBlockForm extends EntityForm {

  /**
   * Constructs an AltrujaBlockForm object.
   *
   * @param \Drupal\Core\Entity\Query\QueryFactory $entity_query
   *   The entity query.
   */
  public function __construct(QueryFactory $entity_query) {
    $this->entityQuery = $entity_query;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.query')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $altruja_block = $this->entity;
    $pages = $this->getAvailablePages();
    $code_options = $this->getCodeOptions($pages);
    if ($altruja_block->isNew() && !$code_options) {
      drupal_set_message($this->t("You don't have any donation pages defined in Altruja. Please go to your <em>MyAltruja</em> page and setup at least one donation page."), 'warning');
      return $form;
    }

    $form['#pages'] = $pages;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $altruja_block->label(),
      '#description' => $this->t("Label for the altruja block."),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $altruja_block->id(),
      '#machine_name' => [
        'exists' => [$this, 'exist'],
      ],
      '#disabled' => !$altruja_block->isNew(),
    ];
    $form['embed_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Type'),
      '#description' => $this->t("The embed type, either page or button."),
      '#options' => [
        'button' => $this->t('Button'),
        'page' => $this->t('Page'),
      ],
      '#default_value' => $altruja_block->embed_type,
      '#required' => TRUE,
    ];
    $form['code'] = [
      '#type' => 'select',
      '#title' => $this->t('Page'),
      '#description' => $this->t("Select your altruja campaign."),
      '#options' => $code_options,
      '#default_value' => $altruja_block->code,
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::updatePageDetails',
        'wrapper' => 'altruja-details-wrapper',
      ],
    ];
    $form['details'] = [
      '#type' => 'item',
      '#title' => $this->t('Page details'),
      '#markup' => $this->getPageDetails($pages[$altruja_block->code]),
      '#states' => [
        'visible'=> [
          ':input[name="code"]' => array('empty' => FALSE),
        ],
      ],
      '#prefix' => '<div id="altruja-details-wrapper">',
      '#suffix' => '</div>',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $altruja_block = $this->entity;
    $status = $altruja_block->save();

    if ($status) {
      drupal_set_message($this->t('Saved the %label altruja block.', [
        '%label' => $altruja_block->label(),
      ]));
    }
    else {
      drupal_set_message($this->t('The %label altruja block was not saved.', [
        '%label' => $altruja_block->label(),
      ]));
    }

    $form_state->setRedirect('entity.altruja_block.collection');
  }

  /**
   * Helper function to check whether an altruja block configuration entity exists.
   */
  public function exist($id) {
    $entity = $this->entityQuery->get('altruja_block')
      ->condition('id', $id)
      ->execute();
    return (bool) $entity;
  }

  /**
   * Retrieve the available altruja pages for this client.
   *
   * @return array
   */
  private function getAvailablePages() {
    $config = $this->config('altruja.settings');
    $client_code = $config->get('altruja.client_code');
    $response = AltrujaAPI::queryEndpoint('pages/' . $client_code);
    if (!$response || empty($response->pages) || !is_array($response->pages)) {
      return NULL;
    }
    $pages = array();
    foreach ($response->pages as $page) {
      $pages[$page->short] = $page;
    }
    ksort($pages);
    return $pages;
  }

  /**
   * Retrieve options for the block selection.
   *
   * @param array $pages
   *   An array of altruja page objects as retrieved from getAvailablePages().
   *
   * @return array
   */
  private function getCodeOptions($pages) {
    return array_map(function($page) {
      $language_info = (!empty($page->language) ? ' (' . $page->language . ')' : '');
      return $page->short . ': ' . $page->displayname . $language_info;
    }, $pages);
  }

  /**
   * Callback for AJAX update of the page details.
   */
  function updatePageDetails($form, FormStateInterface $form_state) {
    $code = $this->entity->code;
    $pages = $form['#pages'];
    $form ['details']['#markup'] = $this->getPageDetails($pages[$code]);
    return $form ['details'];
  }

  /**
   * Retrieve a printable version of the page details for a selected page.
   */
  private function getPageDetails($page) {
    $items = [
      '<strong>' . $page->displayname . '</strong>',
    ];
    if (!empty($page->intro)) {
      $items[] = strip_tags($page->intro);
    }
    if (!empty($page->text)) {
      $items[] = strip_tags($page->text);
    }
    $items[] = $page->collected . ' / ' . $page->target . ' ' . $page->currency;
    return implode('<br />', $items);
  }

}
