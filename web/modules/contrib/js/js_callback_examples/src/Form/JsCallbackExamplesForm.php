<?php

/**
 * @file
 * Contains \Drupal\js_callback_examples\Form\JsCallbackExamplesForm.
 */

namespace Drupal\js_callback_examples\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;

/**
 * JsCallbackExamplesForm.
 */
class JsCallbackExamplesForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'js_callback_examples_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attached']['library'][] = 'js_callback_examples/js_callback_examples';

    $form['intro'] = [
      '#markup' => t('<p>This example page will show you examples on how each of these callbacks work using the JS module API.</p>'),
    ];

    $user = User::load(\Drupal::currentUser()->id());

    $submit = [
      '#type' => 'submit',
      '#value' => t('Send')->render(),
      '#js_callback' => 'js_callback_examples.email',
      '#attributes' => ['data-user' => $user->id()],
    ];
    $submit_code = $submit;
    $description = t('Use <code>#js_callback</code> on elements (like this submit button) to automatically generate the necessary data attributes. Its value should be the identifier of a valid @JsCallback plugin.');
    $description .= '<pre><code>' . trim(htmlentities(var_export($submit_code, TRUE))) . '</code></pre>';
    $description .= '<pre><code>' . trim(htmlentities(preg_replace('/<!--(.*)-->/Uis', '', render($submit_code)))) . '</code></pre>';

    $form['get_uid'] = [
      '#type' => 'fieldset',
      '#title' => t('Get UID (using #js_callback and $.fn.jsCallback())'),
      '#description' => $description,
      '#attributes' => [
        'data-js-type' => 'callback',
      ],
    ];
    $form['get_uid']['email'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Users'),
      '#target_type' => 'user',
      '#default_value' => $user,
    ];

    $form['get_uid']['actions'] = ['#type' => 'actions'];

    $form['get_uid']['actions']['submit'] = $submit;

    $form['get_uid']['results'] = [
      '#type' => 'fieldset',
      '#title' => t('Results'),
      '#attributes' => [
        'class' => [
          'results',
        ],
      ],
      '#value' => '<pre><code></code></pre>',
      // Needs high weight so it appears after actions.
      '#weight' => 1000,
    ];

    $items[] = [
      '#type' => 'link',
      '#title' => $this->t('Node 2'),
      '#url' => Node::load(2)->toUrl(),
      '#js_get' => TRUE,
    ];
    $items[] = [
      '#type' => 'link',
      '#title' => $this->t('Access Denied'),
      '#url' => Url::fromRoute('js_callback_examples.access_denied'),
      '#js_get' => TRUE,
    ];
    $items[] = [
      '#type' => 'link',
      '#title' => $this->t('Redirect'),
      '#url' => Url::fromRoute('js_callback_examples.redirect'),
      '#js_get' => TRUE,
    ];
    $items[] = [
      '#type' => 'link',
      '#title' => $this->t('Admin'),
      '#url' => Url::fromRoute('system.admin'),
      '#js_get' => Url::fromRoute('system.admin'),
    ];
    $items[] = [
      '#type' => 'link',
      '#title' => $this->t('Front Page'),
      '#url' => Url::fromRoute('<front>'),
      '#js_get' => TRUE,
    ];

    $form['using_js_get'] = [
      '#type' => 'fieldset',
      '#title' => t('Using $.jsGet()'),
      '#description' => t('Click the links below to see the results.'),
      '#attributes' => [
        'data-js-type' => 'get',
      ],
      'links' => [
        '#theme' => 'item_list',
        '#items' => $items,
      ],
      'results' => [
        '#type' => 'fieldset',
        '#title' => t('Results'),
        '#attributes' => [
          'class' => [
            'results',
          ],
        ],
        '#value' => '<pre><code></code></pre>',
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Intentionally left empty.
  }

}
