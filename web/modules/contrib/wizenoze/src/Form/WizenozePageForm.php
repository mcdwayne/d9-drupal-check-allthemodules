<?php

namespace Drupal\wizenoze\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\wizenoze\Helper\WizenozeAPI;
use Drupal\Core\ProxyClass\Routing\RouteBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class WizenozePageForm.
 *
 * @package Drupal\wizenoze\Form
 */
class WizenozePageForm extends EntityForm {

  /**
   * Protected routeBuilder variable.
   *
   * @var Drupal\Core\ProxyClass\Routing\RouteBuilder
   */
  protected $routeBuilder;

  /**
   * {@inheritdoc}
   */
  public function __construct(RouteBuilder $routeBuilder) {
    $this->routeBuilder = $routeBuilder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('router.builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /* @var $wizenoze_page \Drupal\wizenoze\WizenozePageInterface */
    $wizenoze_page = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#maxlength' => 255,
      '#default_value' => $wizenoze_page->label(),
      '#required' => TRUE,
      '#description' => $this->t('This will also be used as the page title.'),
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $wizenoze_page->id(),
      '#machine_name' => [
        'exists' => '\Drupal\wizenoze\Entity\Wizenoze::load',
      ],
      '#disabled' => !$wizenoze_page->isNew(),
    ];

    // Default index and states.
    $default_index = $wizenoze_page->getIndex();
    $default_index_states = [
      'visible' => [
        ':input[name="index"]' => ['value' => $default_index],
      ],
    ];

    $index_options = [];
    $wizenoze = WizenozeAPI::getInstance();
    $wizenoze_indexes = $wizenoze->searchEngineList();
    foreach ($wizenoze_indexes as $wizeonze_index) {
      $index_options[$wizeonze_index['id']] = $wizeonze_index['name'];
    }

    $form['index_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Index'),
    ];

    $form['index_fieldset']['index'] = [
      '#type' => 'select',
      '#title' => $this->t('Search Engine'),
      '#options' => $index_options,
      '#default_value' => $default_index,
      '#required' => TRUE,
    ];

    $form['page_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Page'),
      '#states' => [
        'visible' => [':input[name="index"]' => ['value' => $default_index]],
      ],
      '#access' => !empty($default_index),
    ];

    $form['page_fieldset']['path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Path'),
      '#maxlength' => 255,
      '#default_value' => $wizenoze_page->getPath(),
      '#description' => $this->t("Do not include a trailing slash."),
      '#required' => TRUE,
      '#access' => !empty($default_index),
      '#states' => $default_index_states,
    ];

    $form['page_fieldset']['previous_path'] = [
      '#type' => 'value',
      '#value' => $wizenoze_page->getPath(),
      '#access' => !empty($default_index),
    ];

    $form['page_fieldset']['clean_url'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Use clean URL's"),
      '#default_value' => $wizenoze_page->getCleanUrl(),
      '#access' => !empty($default_index),
      '#states' => $default_index_states,
    ];

    $form['page_fieldset']['previous_clean_url'] = [
      '#type' => 'value',
      '#default_value' => $wizenoze_page->getCleanUrl(),
    ];

    $form['page_fieldset']['limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Limit'),
      '#default_value' => $wizenoze_page->getLimit(),
      '#min' => 1,
      '#required' => TRUE,
      '#access' => !empty($default_index),
      '#states' => $default_index_states,
    ];

    $form['page_fieldset']['show_search_form'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show search form above results'),
      '#default_value' => $wizenoze_page->showSearchForm(),
      '#access' => !empty($default_index),
      '#states' => $default_index_states,
    ];

    $form['page_fieldset']['show_all_when_no_keys'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show all results when no search is performed'),
      '#default_value' => $wizenoze_page->showAllResultsWhenNoSearchIsPerformed(),
      '#access' => !empty($default_index),
      '#states' => $default_index_states,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);

    /* @var $search_api_page \Drupal\wizenoze\WizenozePageInterface */
    $wizenoze_page = $this->entity;
    if ($wizenoze_page->isNew()) {
      $actions['submit']['#value'] = $this->t('Next');
    }

    $default_index = $wizenoze_page->getIndex();

    if (!empty($default_index)) {

      // Add an update button that shows up when changing the index.
      $default_index_states_invisible = [
        'invisible' => [
          ':input[name="index"]' => ['value' => $default_index],
        ],
      ];
      $actions['update'] = $actions['submit'];
      $actions['update']['#value'] = $this->t('Update');
      $actions['update']['#states'] = $default_index_states_invisible;

      // Hide the Save button when the index changes.
      $default_index_states_visible = [
        'visible' => [
          ':input[name="index"]' => ['value' => $default_index],
        ],
      ];
      $actions['submit']['#states'] = $default_index_states_visible;
    }

    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /* @var $search_api_page \Drupal\wizenoze\WizenozePageInterface */
    $wizenoze_page = $this->entity;
    $status = $wizenoze_page->save();
    switch ($status) {
      case SAVED_NEW:

        // Redirect to edit form so the rest can be configured.
        $form_state->setRedirectUrl($wizenoze_page->toUrl('edit-form'));
        break;

      default:
        // Set redirect to overview if the index is the same, otherwise, go to
        // the edit form again.
        if ($form_state->getValue('index') == $form_state->getValue('previous_index')) {
          $form_state->setRedirectUrl($wizenoze_page->toUrl('collection'));
          drupal_set_message($this->t('Saved the %label Search page.', [
            '%label' => $wizenoze_page->label(),
          ]));
        }
        else {
          $form_state->setRedirectUrl($wizenoze_page->toUrl('edit-form'));
          drupal_set_message($this->t('Updated the index for the %label Search page.', [
            '%label' => $wizenoze_page->label(),
          ]));
        }
    }
    // Trigger a router rebuild if:
    // - path is different than previous_path.
    // - clean_url is different than previous_clean_url.
    if ($form_state->getValue('path') != $form_state->getValue('previous_path') || $form_state->getValue('clean_url') != $form_state->getValue('previous_clean_url')) {
      $this->routeBuilder->rebuild();
    }
  }

}
