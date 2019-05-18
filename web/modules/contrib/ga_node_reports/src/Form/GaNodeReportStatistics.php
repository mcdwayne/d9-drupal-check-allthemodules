<?php

namespace Drupal\ga_node_reports\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Custom function to get the node wise report.
 */
class GaNodeReportStatistics extends FormBase {

  /**
   * The route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ga_node_reports_statics_form';
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
   * This function construct a form.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The The route match service.
   */
  public function __construct(RouteMatchInterface $routeMatch) {
    $this->routeMatch = $routeMatch;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $start_date = isset($_GET['start_date']) ? strtotime($_GET['start_date']) : strtotime('-6 days');
    $end_date = isset($_GET['end_date']) ? strtotime($_GET['end_date']) : strtotime('now');

    $form['start_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Start date'),
      '#default_value' => date('Y-m-d', $start_date),
      '#description' => $this->t('Ex: @date', ["@date" => date('d/m/Y', $start_date)]),
      '#size' => 10,
    ];

    $form['end_date'] = [
      '#type' => 'date',
      '#title' => $this->t('End date'),
      '#default_value' => date('Y-m-d', $end_date),
      '#description' => $this->t('Ex: @date', ["@date" => date('d/m/Y', $end_date)]),
      '#size' => 10,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Apply'),
      '#prefix' => '<div class="buttons">',
    ];

    $form['reset'] = [
      '#type' => 'submit',
      '#value' => $this->t('Reset'),
      '#submit' => [[$this, 'reset']],
      '#suffix' => '</div>',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Building the query parameter based on the filters.
    $start_date = $form['start_date']['#value'];
    $end_date = $form['end_date']['#value'];

    $nid = $this->routeMatch->getParameter('node');
    $redirect_path = '/node/' . $nid . '/analytics/result?start_date=' . $start_date . '&end_date=' . $end_date;
    $url = url::fromUserInput($redirect_path);
    $form_state->setRedirectUrl($url);

  }

  /**
   * {@inheritdoc}
   */
  public function reset($form, &$form_state) {
    $nid = $this->routeMatch->getParameter('node');

    // Redirect to the same page.
    $redirect_path = '/node/' . $nid . '/analytics';
    $url = url::fromUserInput($redirect_path);
    $form_state->setRedirectUrl($url);
  }

}
