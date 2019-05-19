<?php

namespace Drupal\skillset_inview\Form;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Utility;
use Drupal\Core\Url;

/**
 * Class OrderSkill.
 *
 * @package Drupal\skillset_inview\Form
 */
class OrderSkill extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'skillset_inview_order';
  }


  /**
   * A simple controller method to explain what the tablesort example is about.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [
      '#attached' => [
        'library' => [
          'skillset_inview/admin',
        ],
      ],
    ];

    $db = \Drupal::service('database');
    $results = $db->select('skillset_inview', 's')
      ->fields('s')
      ->orderBy('weight')
      ->execute()
      ->fetchAll();
    $total = count($results);
    $total_rows = $total + 10;

    if ($total > 0) {
      $form['section-heading'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Section Heading'),
        '#default_value' => $this->config('skillset_inview.heading')->get('heading'),
        '#description' => $this->t('Section Heading can be left empty.'),
        '#size' => 23,
        '#weight' => -100,
        '#wrapper_attributes' => [
          'class' => ['section-heading-row'],
        ],
      ];
      if (\Drupal::moduleHandler()->moduleExists('config_translation')) {
        $link = Link::createFromRoute($this->t('Translate Heading'), 'config_translation.item.overview.skillset_inview.order');
        $form['section-heading']['#field_suffix'] = $link->toString();
      }

      $add_skill = Link::createFromRoute($this->t('Add Skill'), 'skillset_inview.add_form');
      $form['skillset-sort'] = [
        '#type' => 'table',
        '#header' => [
          ['data' => $this->t('Skill Name'), 'class' => ['skill-column']],
          ['data' => $this->t('Percent'), 'class' => ['percent-column']],
          ['data' => $this->t('Weight'), 'class' => ['weight-column']],
          ['data' => $this->t('Operation'), 'class' => ['operation-column']],
        ],
        '#empty' => $this->t('No skills have been added yet!  @url', ['@url' => $add_skill->toString()]),
        '#tabledrag' => [
          [
            'action' => 'order',
            'relationship' => 'sibling',
            'group' => 'row-weight',
          ],
        ],
        '#attributes' => ['class' => ['skillset-inview-ordering']],
      ];

      foreach ($results as $row) {
        $form['skillset-sort'][$row->id]['#attributes']['class'][] = 'draggable';
        $form['skillset-sort'][$row->id]['#weight'] = $row->weight;
        $form['skillset-sort'][$row->id]['name'] = [
          '#type' => 'textfield',
          '#default_value' => $row->name,
          '#attributes' => [
            'class' => ['name-column-field'],
          ],
          '#wrapper_attributes' => [
            'class' => ['name-column'],
          ],
        ];
        $form['skillset-sort'][$row->id]['percent'] = [
          '#type' => 'range',
          '#attributes' => [
            'max' => 100,
            'min' => 0,
            'step' => 1,
            'style' => 'width:80%;',
          ],
          '#default_value' => $row->percent,
          '#field_suffix' => '<span class="visual-assist">' . $row->percent . '</span>%',
          '#wrapper_attributes' => [
            'class' => ['percent-column'],
          ],
        ];
        $form['skillset-sort'][$row->id]['weight'] = [
          '#type' => 'weight',
          '#delta' => $total_rows,
          '#title' => $this->t('Weight for ID @id', ['@id' => $row->id]),
          '#title_display' => 'invisible',
          '#default_value' => $row->weight,
          // Classify the weight element for #tabledrag.
          '#attributes' => [
            'class' => ['row-weight'],
          ],
          '#wrapper_attributes' => [
            'class' => ['weight-column'],
          ],
        ];
        $form['skillset-sort'][$row->id]['operations'] = [
          '#type' => 'operations',
          '#wrapper_attributes' => [
            'class' => ['column-operation'],
          ],
        ];
        $form['skillset-sort'][$row->id]['operations']['#links']['view'] = [
          'title' => $this->t('Delete'),
          'attributes' => [
            'title' => $this->t("Delete skill '@name'", ['@name' => trim(strip_tags(Html::normalize($row->name)))]),
          ],
          'url' => Url::fromRoute('skillset_inview.delete_confirm_form', ['skill' => $row->id]),
        ];
      }

      $form['html-description'] = [
        '#markup' => _skillset_inview_allowed_tags_description(),
      ];
      $form['actions'] = ['#type' => 'actions'];
      $form['actions']['save'] = [
        '#type' => 'submit',
        '#value' => $this->t('Save Changes'),
        '#validate' => [
          '::brokenTagCheck',
          '::duplicateCheck',
          '::emptyCheck',
        ],
      ];
      $form['actions']['save']['#dropbutton'] = 'submit';

      // Can't beat destination if Context Link was used.
      $destinations = \Drupal::destination()->getAsArray();
      if ($destinations['destination'] == Url::fromRoute('skillset_inview.order')->toString()) {
        $form['actions']['add-new'] = [
          '#type' => 'submit',
          '#dropbutton' => 'submit',
          '#value' => $this->t('Save Changes then go to Add skill'),
          '#validate' => [
            '::brokenTagCheck',
            '::duplicateCheck',
            '::emptyCheck',
          ],
          '#submit' => ['::submitForm', '::gotoAddNew'],
          '#attributes' => [
            'title' => $this->t('Clears any changes to previous saved state.'),
          ],
        ];
      }

      // If more than 2 exist.
      if ($total > 1) {
        // Add dropbutton to basic save.
        $form['actions']['alpha-sort'] = [
          '#type' => 'submit',
          '#dropbutton' => 'submit',
          '#value' => $this->t('Save & Sort Alpabetically'),
          '#attributes' => [
            'title' => $this->t('Saves current changes, overriding any manual weight sorting.'),
          ],
          '#submit' => ['::alphaSort'],
          '#validate' => [
            '::brokenTagCheck',
            '::duplicateCheck',
            '::emptyCheck',
          ],
        ];
      }
      $form['actions']['reset'] = [
        '#type' => 'submit',
        '#dropbutton' => 'submit',
        '#value' => $this->t('Cancel Changes/Reset Form'),
        '#submit' => ['::cancelChanges'],
        '#attributes' => [
          'title' => $this->t('Clears any changes to previous saved state.'),
        ],
      ];

    }
    else {
      drupal_set_message($this->t('Overview offline.  No skills exist at this time, please add below&hellip;'), 'warning');
      return $this->redirect('skillset_inview.add_form');
    }
    return $form;
  }


  /**
   * Function cancelChanges routine.
   */
  public function cancelChanges() {
    drupal_set_message($this->t('Any previous changes have been abandoned.'), 'status');
  }


  /**
   * Function gotoAddNew routine.
   * @param array $form
   * @param FormStateInterface $form_state
   * @return $this
   */
  public function gotoAddNew(array &$form, FormStateInterface $form_state) {
    return $form_state->setRedirect('skillset_inview.add_form');
  }


  /**
   * Function brokenTagCheck routine.
   * @param array $form
   * @param FormStateInterface $form_state
   */
  public function brokenTagCheck(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue(['skillset-sort']);
    $count = 0;
    foreach ($values as $id => $item) {
      $name_clean = Html::normalize($item['name']);
      if ($name_clean != $item['name']) {
        $count++;
        $form_state->setErrorByName('skillset-sort][' . $id . '][name', $this->t('Skill <a href="#edit-skillset-sort-@linkid-name">Identity @id</a> has disallowed or broken HTML tags.', array('@linkid' => $id, '@id' => $id)));
      }

    }
    if ($count > 0) {
      drupal_set_message(\Drupal::translation()->formatPlural($count, 'An item with broken HTML has been found.  Please correct your input.', 'Items with disallowed or broken HTML have been found.  Please correct your input.'), 'error');
    }
  }


  /**
   * Function emptyCheck routine.
   */
  public function emptyCheck(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue(['skillset-sort']);
    $count = 0;
    foreach ($values as $id => $item) {
      $name_clean = trim(strip_tags(Html::normalize($item['name'])));
      if ($name_clean == '') {
        $count++;
        $form_state->setErrorByName('skillset-sort][' . $id . '][name', $this->t('Skill <a href="#edit-skillset-sort-@linkid-name">Identity @id</a> is empty.', array('@linkid' => $id, '@id' => $id)));
      }

    }
    if ($count > 0) {
      $header_message = \Drupal::translation()->formatPlural($count, '1 skill has been left empty!', '@count skills are empty!');
      drupal_set_message($header_message, 'error');
    }
  }


  /**
   * Form validate handler finds duplicat names in current dataset.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function duplicateCheck(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue(['skillset-sort']);
    $names = array();
    $has_errors = FALSE;
    foreach ($values as $id => $item) {
      $names[$id] = trim(Unicode::strtolower(strip_tags(Html::normalize($item['name']))));
    }

    $dups = array_count_values($names);
    $dups = array_flip($dups);
    unset($dups['1']); /* Keys of just one are fine. */
    foreach ($dups as $err_name) {
      if ($err_name == NULL) {
        /* Empty field error superseeds dup. */
        break;
      }
      $links = '<ul class="error-line-float">';
      foreach ($names as $id => $name) {
        if ($name == $err_name) {
          $links .= '<li><a href="#edit-skillset-sort-' . $id . '-name">[Skill ' . $id . ']</a></li>';
        }
      }
      $links .= '</ul>';
      foreach ($names as $id => $name) {
        if ($name == $err_name) {
          $has_errors = TRUE;
          $form_state->setErrorByName('skillset-sort][' . $id . '][name', $this->t('<q>@name</q> is a replica.  !links', array('@name' => $name, '!links' => $links)));
        }
      }

    }
    if ($has_errors) {
      drupal_set_message($this->t('Duplicate skills have been detected.'), 'error');
    }
  }


  /**
   * Form submission handler for the 'Sort Alpabetically' action.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function alphaSort(array &$form, FormStateInterface $form_state) {
    $header_raw = $form_state->getValue(['section-heading']);
    $header_filter = Html::normalize($header_raw);
    $allows_tags = _skillset_inview_allowed_tags();
    $header_strip = Xss::filter($header_filter, $allows_tags);
    \Drupal::configFactory()
      ->getEditable('skillset_inview.heading')
      ->set('heading', $header_strip)
      ->save();

    $values = $form['skillset-sort']['#value'];
    $keyed_list_htmlless = array();
    // Get a delta count, make negative for natsort.
    $delta = (count($values)) * -1;
    foreach ($values as $key => $item) {
      $keyed_list_htmlless[$key] = trim(strip_tags(Html::normalize($item['name'])));
    }
    natcasesort($keyed_list_htmlless);
    foreach ($keyed_list_htmlless as $id => $name) {
      $db = \Drupal::service('database');
      $db->update('skillset_inview')
        ->fields([
          'weight' => $delta,
          'percent' => $values[$id]['percent'],
          'name' => Xss::filter(Html::normalize($values[$id]['name']), $allows_tags),
        ])
        ->condition('id', $id, '=')
        ->execute();
      $delta++;
    }

    Cache::invalidateTags(['rendered']);
    drupal_set_message($this->t('Skillsets have been updated and alphabetized.'), 'status');
  }


  /**
   * Submit routine.
   * @param array $form
   * @param FormStateInterface $form_state
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Clean and save heading.
    $allows_tags = _skillset_inview_allowed_tags();
    $header_raw = $form_state->getValue(['section-heading']);
    $header_filter = Html::normalize($header_raw);
    $header_strip = Xss::filter($header_filter, $allows_tags);
    \Drupal::configFactory()
      ->getEditable('skillset_inview.heading')
      ->set('heading', $header_strip)
      ->save();

    // Clean and save rows.
    $skills = ($form_state->getValue(['skillset-sort']) ? $form_state->getValue(['skillset-sort']) : FALSE);
    if (!empty($skills)) {
      foreach ($skills as $id => $item) {
        $html_fix = Html::normalize($item['name']);
        $html_allow = Xss::filter($html_fix, $allows_tags);
        $db = \Drupal::service('database');
        $db->update('skillset_inview')
          ->fields([
            'weight' => $item['weight'],
            'name' => $html_allow,
            'percent' => $item['percent'],
          ])
          ->condition('id', $id, '=')
          ->execute();
      }
    }

    Cache::invalidateTags(['rendered']);
    drupal_set_message($this->t('Skillsets have been updated.'), 'status');
  }

}
