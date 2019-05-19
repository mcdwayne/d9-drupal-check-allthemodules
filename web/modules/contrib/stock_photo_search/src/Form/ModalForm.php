<?php

namespace Drupal\stock_photo_search\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use GuzzleHttp\Client;

/**
 * ModalForm class.
 */
class ModalForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'stock_photo_search_modal_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $options = NULL) {

    // Libraries.
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
    $form['#attached']['library'][] = 'stock_photo_search/my_styles';
    $form['#attached']['library'][] = 'stock_photo_search/my_scripts';

    $form['#prefix'] = '<div id="modal_search_photo_form">';
    $form['#suffix'] = '</div>';

    // The status messages that will contain any form errors.
    $form['status_messages'] = [
      '#type' => 'status_messages',
      '#weight' => -10,
    ];

    // Search group.
    $form['search_group'] = [
      '#type' => 'details',
      '#title' => $this->t('Search'),
      '#open' => TRUE,
    ];

    $form['search_group']['searchText'] = [
      '#prefix' => '<div class="stock_photo_search_search_container">',
      '#type' => 'search',
      '#default_value' => '',
      '#title' => $this->t('Search for free photos'),
      '#size' => 40,
    ];

    $form['search_group']['btnsearch'] = [
      '#suffix' => '</div>',
      '#type' => 'button',
      '#value' => $this->t('Search'),
      '#attributes' => [
        'class' => [
          'use-ajax',
        ],
      ],
      '#ajax' => [
        'callback' => '::submitSearch',
        'wrapper' => 'images-results',
        'event' => 'click',
        'progress' => [
          'type' => 'throbber',
          'message' => NULL,
        ],
      ],
    ];

    // Results group.
    $form['results_group'] = [
      '#type' => 'details',
      '#title' => $this->t('Results'),
      '#open' => TRUE,
    ];

    $form['results_group']['images_results'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'images-results'],
    ];

    return $form;
  }

  /**
   * AJAX callback handler that displays search results.
   */
  public function submitSearch(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    $type = \Drupal::service('stock_photo_search.provider_manager');
    $plugin_definitions = $type->getDefinitions();

    $provider = \Drupal::request()->query->get('provider');

    $http_client = new Client();
    $api = new $plugin_definitions[$provider]['class'](['searchValue' => $form_state->getValue('searchText')],
                                                                      $provider,
                                                                      $plugin_definitions,
                                                                      $http_client);

    $results = $api->searchFromApi($form_state->getValue('searchText'));

    if (count($results) > 0) {

      $form['results_group']['images_results']['results'] = [
        '#type' => 'table',
        '#header' => [$this->t("Images"), $this->t("Sizes")],
      ];

      for ($i = 0; $i < count($results); $i++) {
        $form['results_group']['images_results']['results'][$i]['img'] = [
          '#type' => 'item',
          '#markup' => '<img src="' . $results[$i]['small'] . '"/>',
        ];

        $form['results_group']['images_results']['results'][$i]['container'] = [
          '#type' => 'container',
        ];

        $valueRadios = [
          $results[$i]['original'] => $this->t('Original'),
          $results[$i]['small'] => $this->t('Small'),
          $results[$i]['medium'] => $this->t('Medium'),
          $results[$i]['large'] => $this->t('Large'),
        ];
        $form['results_group']['images_results']['results'][$i]['container']['sizes'] = [
          '#title' => $this->t('Select a size for your stock photo'),
          '#type' => 'select',
          '#options' => $valueRadios,
          '#attributes' => [
            'class' => [
              'select-image-size',
            ],
            'id' => 'select_photo_' . $i,
            'tagIdPhoto' => $results[$i]['id'],
          ],
        ];

        $form['results_group']['images_results']['results'][$i]['container']['btnSelect'] = [
          '#type' => 'button',
          '#value' => $this->t('Select this Photo'),
          '#attributes' => [
            'class' => [
              'btnStockPhotoSearchSelect',
            ],
            'tag' => 'select_photo_' . $i,
          ],
        ];
      }

      $response->addCommand(new OpenModalDialogCommand($this->t("Search Form"),
                                                       $form,
                                                       ['width' => 800]));
      return $response;
    }
    else {
      // No results.
      $response->addCommand(new OpenModalDialogCommand($this->t("Upps!"),
                                                       $this->t('No results for this search.'),
                                                       ['width' => 800]));
      return $response;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return ['config.stock_photo_search_modal_form'];
  }

}
