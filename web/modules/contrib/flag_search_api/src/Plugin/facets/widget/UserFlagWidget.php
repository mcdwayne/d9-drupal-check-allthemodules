<?php

namespace Drupal\flag_search_api\Plugin\facets\widget;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\facets\Result\Result;
use Drupal\facets\FacetInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\facets\Widget\WidgetPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The user_flag widget.
 *
 * @FacetsWidget(
 *   id = "user_flag",
 *   label = @Translation("User Flags"),
 *   description = @Translation("A simple widget that shows a single checkbox of a user's flagged content by Flag Search API"),
 * )
 */
class UserFlagWidget extends WidgetPluginBase implements ContainerFactoryPluginInterface {

  /**
   * AccountProxy.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'flags_label' => "My Flagged Items",
      'no_flags_label' => "No Flagged Items Available",
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet) {
    // Get current user ID.
    $uid = $this->currentUser->id();
    $userFlagResult = FALSE;

    // Get results.
    $results = $facet->getResults();
    foreach ($results as $result) {
      // Select a result that matches the current user.
      if ($result->getRawValue() == $uid) {
        $userFlagResult = $result;
        $userFlagResult->setDisplayValue($this->getConfiguration()['flags_label']);
      }
    }

    if ($userFlagResult) {
      // Replace all results with the selected result.
      $facet->setResults([$userFlagResult]);
    }
    else {
      // Replace all results with an empty result.
      $emptyResult = new Result($facet, $uid, $this->getConfiguration()['no_flags_label'], 0);
      $facet->setResults([$emptyResult]);
    }

    // Go through normal build process with checkboxes.
    $build = parent::build($facet);
    $build['#attributes']['class'][] = 'js-facets-checkbox-links';
    $build['#attached']['library'][] = 'facets/drupal.facets.checkbox-widget';
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, FacetInterface $facet) {
    $form = parent::buildConfigurationForm($form, $form_state, $facet);

    $form['flags_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Flags label'),
      '#description' => $this->t('This text will be used for the flags checkbox label.'),
      '#default_value' => $this->getConfiguration()['flags_label'],
    ];

    $form['no_flags_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('No Flags label'),
      '#description' => $this->t("This text will be used when there aren't any matching flags."),
      '#default_value' => $this->getConfiguration()['no_flags_label'],
    ];

    return $form;
  }

}
