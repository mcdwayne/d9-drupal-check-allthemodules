<?php

namespace Drupal\domain_finder\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\SafeMarkup;

/**
 * Form controller for the content_entity_example entity edit forms.
 *
 * @ingroup content_entity_example
 */
class DomainFinderSearchForm extends FormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'domain_finder_search_form';
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::buildForm().
   */
  public function buildForm(array $form, FormStateInterface $form_state, $config = NULL) {
    $input = $form_state->getUserInput();
    $in_form = 0;
    $exts = [];
    $block_id = '';

    if (!is_array($config)) {
      $in_form = \Drupal::config('domain_finder.settings')->get('domains_in_form');
      $exts = \Drupal::config('domain_finder.settings')->get('domains');
    }
    else {
      $in_form = $config['domains_in_form'];
      $exts = $config['domains'];
      $block_id = $config['block_id'];
    }

    $form['domains_in_form'] = [
      '#type' => 'hidden',
      '#value' => $in_form,
    ];

    $form['block_id'] = [
      '#type' => 'hidden',
      '#value' => $block_id,
    ];

    $form['domain_text'] = [
      '#type' => 'textfield',
      '#default_value' => isset($_GET['domain_text']) ? $_GET['domain_text'] : '',
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Search'),
    ];

    if (!empty($in_form)) {
      $form['exts'] = [
        '#type' => 'container',
        '#tree' => TRUE,
        '#attributes' => ['class' => ['domain-finder-exts']],
      ];
      $current_path = \Drupal::service('path.current')->getPath();
      foreach (array_filter($exts) as $domain) {
        if (empty($_GET) ||
            $current_path != '/domain-finder' ||
            empty($_GET['block_id']) ||
            (!empty($_GET['block_id']) && $block_id != $_GET['block_id'])) {
          $value = 1;
        }
        else {
          $value = isset($_GET['domains'][$domain]);
        }
        $form['exts'][$domain] = [
          '#type' => 'checkbox',
          '#title' => SafeMarkup::checkPlain(".$domain"),
          '#default_value' => $value,
          '#attributes' => ['class' => ['domain-finder-item-exts']],
        ];
      }
    }

    // Attached css to form.
    $form['#attached']['library'][] = 'domain_finder/domain_finder.form';

    return $form;
  }

  /**
   * Form submission handler.
   *
   * @param FormStateInterface $form
   *   An associative array containing the structure of the form.
   * @param array $form_state
   *   An associative array containing the current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $input = $form_state->getUserInput();
    $query = [
      'domain_text' => $input['domain_text'],
      'domains_in_form' => $input['domains_in_form'],
      'block_id' => $input['block_id'],
      'domains' => [],
    ];
    $domains = $input['exts'];

    if (!empty($domains)) {
      if ($query['domains_in_form'] !== 0) {
        $domains = array_filter($domains);
      }
      foreach (array_keys($domains) as $domain) {
        $query['domains'][$domain] = $domain;
      }
    }

    $form_state->setRedirect('domain_finder.view', $query);
  }

}
