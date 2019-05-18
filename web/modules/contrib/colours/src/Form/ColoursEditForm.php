<?php

namespace Drupal\colours\Form;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;

/**
 * Controller for image style edit form.
 */
class ColoursEditForm extends ColoursFormBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('colours')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $user_input = $form_state->getUserInput();
    $form['#title'] = $this->t('Edit colours %name', ['%name' => $this->entity->label()]);
    $form['#tree'] = TRUE;

    // Build the list of existing image effects for this image style.
    $form['colourset'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Colour Title'),
        $this->t('CSS Selector'),
        $this->t('Background Colour'),
        $this->t('Foreground Colour'),
        $this->t('Weight'),
        $this->t('Operations'),
      ],
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'colours-order-weight',
        ],
      ],
      '#attributes' => [
        'id' => 'colours-colourset',
      ],
      '#empty' => t('There are currently no colours in this colourset. Add one by selecting an option below.'),
      '#weight' => 5,
    ];
    foreach ($this->entity->getColoursetMappings() as $colour) {
      $key = $colour->getUuid();
      $form['colourset'][$key]['#attributes']['class'][] = 'draggable';
      $form['colourset'][$key]['#weight'] = isset($user_input['colourset']) ? $user_input['colourset'][$key]['weight'] : NULL;
      $form['colourset'][$key]['colour'] = [
        '#tree' => FALSE,
        'data' => [
          'label' => [
            '#plain_text' => $colour->label(),
          ],
        ],
      ];

      $summary = $colour->getSummary();

      if (!empty($summary)) {
        $summary['#prefix'] = ' ';
        $form['colourset'][$key]['colour']['data']['summary'] = $summary;
      }

      $form['colourset'][$key]['weight'] = [
        '#type' => 'weight',
        '#title' => $this->t('Weight for @title', ['@title' => $colour->label()]),
        '#title_display' => 'invisible',
        '#default_value' => $colour->getWeight(),
        '#attributes' => [
          'class' => ['colourset-order-weight'],
        ],
      ];
    }

    $form['colourset']['new'] = [
      '#tree' => FALSE,
      '#weight' => isset($user_input['weight']) ? $user_input['weight'] : NULL,
      '#attributes' => ['class' => ['draggable']],
    ];
    $form['colourset']['new']['colour'] = [
      'data' => [
        [
          'title' => [
            '#type' => 'textfield',
            '#title' => $this->t('Title'),
            '#title_display' => 'invisible',
            '#maxlength' => 255,
            '#size' => 20,
            '#default_value' => '',
            '#required' => TRUE,
          ],
          'css_selector' => [
            '#type' => 'textfield',
            '#title' => $this->t('CSS Selector'),
            '#title_display' => 'invisible',
            '#maxlength' => 500,
            '#size' => 10,
            '#default_value' => $this->entity->id() . "_" . $form['colourset']['new']['colour']['#weight'] ,
            '#required' => TRUE,
          ],
          'background' => [
            '#type' => 'textfield',
            '#title' => $this->t('Background Colour'),
            '#title_display' => 'invisible',
            '#maxlength' => 500,
            '#size' => 10,
            '#default_value' => '#ffffff',
            '#required' => TRUE,
          ],
          'foreground' => [
            '#type' => 'textfield',
            '#title' => $this->t('Foreground Colour'),
            '#title_display' => 'invisible',
            '#maxlength' => 500,
            '#size' => 10,
            '#default_value' => '#000000',
            '#required' => TRUE,
          ],
          'add' => [
            '#type' => 'submit',
            '#value' => $this->t('Add Colour'),
//            '#submit' => ['::submitForm', '::colourSave'],
            '#ajax' => [
              'callback' => ['::submitForm', '::colourSave'],
              'effect' => 'fade',
              'event' => 'change',
              'progress' => [
                'type' => 'throbber',
                'message' => NULL,
              ],
             ]
          ],
          
        ],
      ],
      '#prefix' => '<div class="colour-new">',
      '#suffix' => '</div>',
    ];

    $form['colourset']['new']['weight'] = [
      '#type' => 'weight',
      '#title' => $this->t('Weight for new Colour'),
      '#title_display' => 'invisible',
      '#default_value' => count($this->entity->getColoursetMappings()) + 1,
      '#attributes' => ['class' => ['colourset-order-weight']],
    ];
    $form['colourset']['new']['operations'] = [
      'data' => [],
    ];

    return parent::form($form, $form_state);
  }


  /**
   * Submit handler for image effect.
   */
  public function colourSave($form, FormStateInterface $form_state) {
    $this->save($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if (!$form_state->isValueEmpty('colourset')) {
      $this->updateColoursWeights($form_state->getValue('Colours'));
    }
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);
    drupal_set_message($this->t('Changes to Colours have been saved.'));
  }

  /**
   * {@inheritdoc}
   */
  public function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Update Colours');

    return $actions;
  }

  /**
   * Updates image colours weights.
   *
   * @param array $effects
   *   Associative array with effects having effect uuid as keys and array
   *   with effect data as values.
   */
  protected function updateColoursWeights(array $effects) {
    foreach ($effects as $uuid => $effect_data) {
      if ($this->entity->getColours()->has($uuid)) {
        $this->entity->getColour($uuid)->setWeight($effect_data['weight']);
      }
    }
  }

}
