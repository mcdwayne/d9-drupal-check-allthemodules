<?php

namespace Drupal\competition\Form;

use Drupal\Component\Utility\NestedArray;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityConstraintViolationListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\serialization\Encoder\JsonEncoder;
use Drupal\competition\CompetitionEntryInterface;
use Drupal\competition\CompetitionManager;

/**
 * Form controller for Competition entry edit forms.
 *
 * @ingroup competition
 */
class CompetitionEntryForm extends ContentEntityForm {

  /**
   * The JSON serializer.
   *
   * @var \Drupal\serialization\Encoder\JsonEncoder
   */
  protected $serializerJson;

  /**
   * The Competition bundle entity for this entry.
   *
   * @var \Drupal\competition\CompetitionInterface
   */
  protected $competition;

  /**
   * The competition manager.
   *
   * @var \Drupal\competition\CompetitionManager
   */
  protected $competitionManager;

  /**
   * Constructs a ContentEntityForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\serialization\Encoder\JsonEncoder $serializer_encoder_json
   *   The JSON serializer.
   */
  public function __construct(EntityManagerInterface $entity_manager, CompetitionManager $competition_manager, JsonEncoder $serializer_encoder_json) {
    parent::__construct($entity_manager);

    $this->competitionManager = $competition_manager;
    $this->serializerJson = $serializer_encoder_json;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('competition.manager'),
      $container->get('serializer.encoder.json')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditedFieldNames(FormStateInterface $form_state) {
    return array_merge(['type', 'cycle', 'uid'], parent::getEditedFieldNames($form_state));
  }

  /**
   * {@inheritdoc}
   */
  protected function flagViolations(EntityConstraintViolationListInterface $violations, array $form, FormStateInterface $form_state) {
    $has_reentry_violation = FALSE;

    // Manually flag violations of fields not handled by the form display.
    foreach ($violations->getByField('uid') as $violation) {
      $form_state->setErrorByName('uid', $violation->getMessage());
    }

    foreach ($violations->getByField('cycle') as $violation) {
      $form_state->setErrorByName('cycle', $violation->getMessage());

      if ($violation->getCause() == 'field_reentry') {
        $has_reentry_violation = TRUE;
      }
    }

    // TODO: Force validation messages to appear on main entry form, not on the
    // standalone version of the now non-modal reentry form.
    if ($has_reentry_violation) {
      $form_state->setValue('has_reentry_violation', $has_reentry_violation);
    }

    parent::flagViolations($violations, $form, $form_state);
  }

  /**
   * Retrieves validation errors for the supplied CompetitionEntry.
   *
   * @param \Drupal\competition\CompetitionEntryInterface $entry
   *   The CompetitionEntry entity.
   * @param array $form
   *   The form array.
   *
   * @return array
   *   The validation errors found for the CompetitionEntry.
   */
  public function getViolations(CompetitionEntryInterface &$entry, array &$form) {

    $validations = array();

    $violations = $entry->validate();
    for ($i = 0; $i < $violations->count(); $i++) {

      $field = $violations[$i]->getPropertyPath();

      $label = '';
      if (!empty($form[$field]['widget']['#title'])) {
        $label = $form[$field]['widget']['#title'];
      }
      elseif (!empty($form[$field]['widget'][0]['#title'])) {
        $label = $form[$field]['widget'][0]['#title'];
      }

      $validations[] = (object) array(
        'field' => $field,
        'label' => $label,
        'message' => $violations[$i]->getMessage(),
      );
    }

    // Suppress the standard core output from validate() call; this method is
    // purely to gather current violations for alternate UI presentation.
    drupal_get_messages('error');

    return $validations;
  }

  /**
   * Ajax callback to display a modal.
   */
  public function getViolationsModal(array &$form, FormStateInterface &$form_state) {
    $items = array();

    $validations = $this->getViolations($this->entity, $form);
    foreach ($validations as $validation) {
      $items[] = $this->t('%label: @message', [
        '%label' => $validation->label,
        '@message' => $validation->message,
      ]);
    }

    $build['violations'] = array(
      '#theme' => 'item_list',
      '#items' => $items,
    );

    $response = new AjaxResponse();
    $response->addCommand(
      new OpenModalDialogCommand(
        $form['actions']['validate']['#value'],
        $build,
        $this->serializerJson->decode($form['actions']['validate']['#attributes']['data-dialog-options'], 'json')
      )
    );

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Set entry status (Created or Updated).
    // @see competition.settings.statuses
    $status = $this->entity->getStatus();
    $this->entity
      ->setStatus($this->entity->isNew() ? CompetitionEntryInterface::STATUS_CREATED : CompetitionEntryInterface::STATUS_UPDATED);

    // Determine if the "re-entry" version of the form should be presented per
    // routing argument.
    // @see CompetitionEntryController::getForm()
    $storage = $form_state->getStorage();
    $this->entity->isReentry = (!empty($storage['is_reentry']) && $storage['is_reentry']);

    /* @var $entry \Drupal\competition\Entity\CompetitionEntry */
    $entry = $this->entity;

    // Get the competition the entry belongs to, and its entry limits.
    $this->competition = $this->entityManager
      ->loadEntityByConfigTarget(
        $entry->getEntityType()->getBundleEntityType(),
        $entry->bundle()
      );

    $limits = $this->competition
      ->getEntryLimits();

    // Check for STATUS_FINALIZED on this entry, reset and redirect to
    // canonical entity view if true.
    if (in_array($status, [CompetitionEntryInterface::STATUS_FINALIZED, CompetitionEntryInterface::STATUS_ARCHIVED])) {
      $this->entity
        ->setStatus($status);

      // Send user to read-only entry view.
      if ($this->currentUser()->id() == $entry->getOwnerId()) {
        return $this->redirect('entity.competition_entry.canonical', [
          'competition_entry' => $entry->id(),
        ]);
      }
    }

    // Before we run parent::buildForm(), get the temp values and store into
    // form state. Thus, widgets can access and use them to populate while
    // they're building. This should only be needed for widgets with a more
    // complex nested array structure - basic ones are handled generically
    // further below.
    $field_data_temp = $entry->getTempData();
    $form_state->set('field_data_temp', $field_data_temp);

    // Widgets must report that they have processed temp values by adding to
    // this array:
    // $form_state->set(['field_data_temp_processed', $field_name], TRUE);.
    $form_state->set('field_data_temp_processed', []);

    // Build the entry form.
    $form = parent::buildForm($form, $form_state);
    $form = array_merge($form, array(
      '#title' => $this->t('@cycle @label entry', [
        '@cycle' => $entry->getCycle(),
        '@label' => $this->competition->getLabel(),
      ]
      ),
      '#theme' => array('competition_entry_form'),
      '#attached' => array(
        'library' => [
          'competition/competition',
        ],
        'drupalSettings' => [
          'competition' => [
            // Values will be populated after all local form alter is complete.
            'entry' => [],
          ],
        ],
      ),
    ));

    // Prepend form with competition description.
    $form['description'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => [
          'description',
        ],
      ),
      '#weight' => -200,
    );

    $form['description']['description'] = array(
      '#markup' => $this->competition->getLongtext()->description,
    );

    // Add the reentry link to form if configured; click yields modal.
    if (!empty($limits->field_reentry)) {
      $form['reentry'] = array(
        '#type' => 'container',
        '#weight' => -190,
        '#attributes' => array(
          'class' => [
            'container-inline',
            'reenter',
          ],
        ),
      );

      $form['reentry']['link'] = array(
        '#type' => 'link',
        '#title' => $this->t('Re-enter the @cycle @label', [
          '@cycle' => $this->competition->getCycle(),
          '@label' => $this->competition->label(),
        ]),
        '#url' => Url::fromRoute('entity.competition_entry.reenter_form', [
          'competition' => $this->competition->id(),
        ], [
          'query' => $this->getRedirectDestination()->getAsArray(),
        ]),
        '#attributes' => array(
          'class' => [
            'use-ajax',
          ],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => $this->serializerJson->encode([
            'width' => '40%',
          ], 'json'),
        ),
      );
    }

    // Disallow access to these fields for non-admins.
    $form['uid']['#access'] =
    $form['cycle']['#access'] =
    $form['status']['#access'] =
    $form['weight']['#access'] = $this
      ->currentUser()
      ->hasPermission('administer competition entries');

    // Disallow access to all fields that are not $limits->field_reenttry, or
    // not required for form functionality.
    $elements = Element::children($form);
    $elements = array_diff($elements, [
      'actions',
    ]);
    if ($entry->isReentry) {
      foreach ($elements as $key) {
        $form[$key]['#access'] = ($key == $limits->field_reentry);
      }

      // Customize the single reentry field.
      $form[$limits->field_reentry] = array_merge($form[$limits->field_reentry], array(
        '#prefix' => '<p>' . $this->t('Provide the @field you used to enter the %cycle %label the first time.', [
          '@field' => strtolower($form[$limits->field_reentry]['widget']['#title']),
          '%cycle' => $this->competition->getCycle(),
          '%label' => $this->competition->label(),
        ]) . '</p>',
        '#attributes' => array(
          'class' => [
            'container-inline',
          ],
        ),
      ));

      $form['actions']['submit'] = array_merge($form['actions']['submit'], array(
        '#ajax' => [
          'callback' => [$this, 'validateFormAjax'],
        ],
      ));
    }

    // Check if entry status has not yet reached Finalized (i.e. it has not been
    // fully submitted and saved yet).
    $not_final_submitted = in_array($entry->getStatus(), array(CompetitionEntryInterface::STATUS_CREATED, CompetitionEntryInterface::STATUS_UPDATED));

    $form['actions']['submit'] = array_merge($form['actions']['submit'], array(
      '#name' => 'submit',
      '#value' => ($not_final_submitted ? $this->t('Enter') : $this->t('Save')),
      '#weight' => -90,
      '#attributes' => [
        'class' => [
          'save-final',
        ],
      ],
    ));

    // Allow users to save and bypass full entry validation.
    // Only include this functionality while entry is not yet fully submitted.
    $validations = [];
    if (!empty($limits->allow_partial_save) && $not_final_submitted) {

      // Get the list of validations.
      $validations = $this->getViolations($entry, $form);

      $form['actions']['submit']['#value'] = $this->t('Save and Submit');

      $form['actions']['submit_temp'] = array_merge($form['actions']['submit'], array(
        '#name' => 'submit_temp',
        '#value' => $this->t('Save For Later'),
        '#weight' => -100,
        '#attributes' => [
          'class' => [
            'save-temp',
          ],
        ],
        '#submit' => [
          '::submitForm',
          '::saveForLater',
        ],
        '#limit_validation_errors' => [],
      ));

      // Add the "What's missing?" modal validator button and progress bar.
      // @see Drupal.behaviors.competition
      $form['actions']['validate'] = array(
        '#type' => 'submit',
        '#value' => $this->t("What's Missing?"),
        '#weight' => -80,
        '#ajax' => [
          'accepts' => 'application/vnd.drupal-modal',
          'callback' => array($this, 'getViolationsModal'),
        ],
        '#attributes' => array(
          'class' => [
            'validate',
          ],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => $this->serializerJson->encode([
            'width' => '50%',
          ], 'json'),
        ),
        '#limit_validation_errors' => [],
      );

      $form['actions']['progress'] = array(
        '#type' => 'container',
        '#weight' => -120,
        '#attributes' => [
          'class' => [
            'container-inline',
            'progress',
          ],
          'title' => [
            'This progress bar shows how many required fields are filled out.',
          ],
        ],
      );

      // Set initial progress attributes.
      $elements_required = 0;
      foreach ($elements as $element) {
        if (!empty($form[$element]['#access']) && !empty($form[$element]['widget'])) {
          if (!empty($form[$element]['widget']['#required'])) {
            $elements_required++;
          }
        }
      }

      $form['actions']['progress']['progress'] = array(
        '#markup' => '<progress max="' . $elements_required . '" value="' . ($elements_required - count($validations)) . '"></progress>',
      );

      $form['actions']['progress']['description_finalized'] = array(
        '#markup' => '<p>' . $this->t('Note: You will not be able to edit your entry once it has been submitted. Not ready to submit? Save your entry for later.') . '</p>',
      );
    }

    // Repopulate temp field values.
    $this->buildFormRepopulateTempData($form, $form_state);

    // Populate the JSON entry settings for front-end.
    // @see Drupal.behaviors.competition
    $form['#attached']['drupalSettings']['competition']['entry'] = array(
      'limits' => [
        'allowPartialSave' => (bool) $limits->allow_partial_save,
      ],
      'validations' => $validations,
    );

    return $form;
  }

  /**
   * Custom helper to repopulate temporary data stored by "Save for later".
   *
   * {@inheritdoc}
   */
  private function buildFormRepopulateTempData(array &$form, FormStateInterface $form_state) {
    // Temp data is loaded and placed in form state at beginning of buildForm().
    $field_data_temp = $form_state->get('field_data_temp');
    if (empty($field_data_temp)) {
      return;
    }

    // If form is rebuilding during file upload AJAX processing, don't mess
    // with values.
    $trigger = $form_state->getTriggeringElement();
    if (!empty($trigger) && !empty($trigger['#submit']) && in_array('file_managed_file_submit', $trigger['#submit'])) {
      return;
    }

    $elements = Element::children($form);

    // Any widgets that have already repopulated their temp data while building
    // their own forms should report this to bypass the generic process, i.e.:
    // // $form_state->set(['field_data_temp_processed', $field_name], TRUE);.
    $field_data_temp_processed = $form_state->get('field_data_temp_processed');

    foreach ($elements as $element) {
      if ($element == 'actions') {
        continue;
      }

      if (!empty($form[$element]['#access']) && !empty($form[$element]['widget'])) {
        if ($field_data_temp && array_key_exists($element, $field_data_temp)) {

          if (!empty($field_data_temp_processed[$element])) {
            continue;
          }

          // Special handling for datetime fields - these get expanded into
          // 'date' and/or 'time' subfields later, pulling from a
          // #default_value that must be a DrupalDateTime object.
          // We reconstruct raw inputs into this object if possible.
          if (!empty($form[$element]['widget'][0]['value']['#type']) && $form[$element]['widget'][0]['value']['#type'] == 'datetime') {

            $parents = $field_data_temp[$element]['parents'];
            $input = $field_data_temp[$element]['input'];

            $date_val = NULL;
            $time_val = NULL;

            // If both date and time exist, input will be an array; otherwise
            // the single value's key will be last key in parents.
            if (is_array($input)) {
              if (!empty($input['date'])) {
                $date_val = $input['date'];
              }
              if (!empty($input['time'])) {
                $time_val = $input['time'];
              }
            }
            elseif (is_string($input)) {
              // Field settings do not support storing a time without a date,
              // so we expect 'date' here.
              $which = array_pop($parents);
              if ($which == 'date') {
                $date_val = $input;
              }
            }

            $parts = array();

            if (!empty($date_val)) {
              $date_parts = explode('-', $date_val);
              if (count($date_parts) == 3) {
                $parts['year'] = $date_parts[0];
                $parts['month'] = $date_parts[1];
                $parts['day'] = $date_parts[2];
              }
            }
            if (!empty($time_val)) {
              $time_parts = explode(':', $time_val);
              $parts['hour'] = $time_parts[0];
              $parts['minute'] = (isset($time_parts[1]) ? $time_parts[1] : 0);
              $parts['second'] = (isset($time_parts[2]) ? $time_parts[2] : 0);
            }

            if (!empty($parts)) {
              // The widget knows which timezone to use for the input.
              // Since we stored raw input, we simply want to display it again
              // in the same timezone.
              // @see DateTimeWidgetBase::formElement()
              $timezone_name = $form[$element]['widget'][0]['value']['#date_timezone'];

              // This should throw an exception for invalid values in $parts.
              try {
                $dt = DrupalDateTime::createFromArray($parts, new \DateTimeZone($timezone_name));
                $form[$element]['widget'][0]['value']['#default_value'] = $dt;
              }
              catch (\Exception $e) {
                // No need to do anything here - we're not validating at this
                // point, just re-populating.
              }
            }

          }
          else {
            // Non-date fields.
            // (If saved properly, 'parents' and 'input' should always exist.
            // Check is a precaution for bad data or unexpected field widget
            // structures.)
            // Use isset() because 'parents' may be empty array, which is valid.
            if (isset($field_data_temp[$element]['parents']) && !empty($field_data_temp[$element]['input'])) {

              $input = $field_data_temp[$element]['input'];

              // Correct input structure for file fields.
              if (!empty($form[$element]['widget'][0]['#type']) && $form[$element]['widget'][0]['#type'] == 'managed_file') {

                if (isset($input['fids']) && !is_array($input['fids'])) {
                  // A single file upload comes in as 'fids' => $fid, but
                  // #default_value needs 'fids' => array( $fid ).
                  if (!empty($input['fids'])) {
                    $input['fids'] = array(
                      $input['fids'],
                    );
                  }
                  else {
                    $input['fids'] = array();
                  }
                }
              }

              // Set default value.
              NestedArray::setValue($form[$element]['widget'], array_merge($field_data_temp[$element]['parents'], ['#default_value']), $input);
            }

          }
        }
      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $entity = parent::validateForm($form, $form_state);

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function validateFormAjax(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    $response->addCommand(new RedirectCommand(Url::fromRoute('entity.competition_entry.add_form', [
      'competition' => $this->competition->id(),
    ])->toString()));

    return $response;
  }

  /**
   * Submit handler for the "Save for later" button.
   */
  public function saveForLater(array $form, FormStateInterface $form_state) {
    // Save the entry if its new.
    if ($this->entity->isNew()) {
      parent::save($form, $form_state);
    }

    // Save user input via user.data service.
    $this->entity
      ->setTempData($form_state->getUserInput())
      ->setStatus(CompetitionEntryInterface::STATUS_UPDATED)
      ->save();

    drupal_set_message($this->t('Your entry to the %cycle %label has been saved. Come back to update it any time!', [
      '%cycle' => $this->competition->getCycle(),
      '%label' => $this->competition->label(),
    ]));

    $form_state->setRedirect('entity.competition_entry.edit_form', [
      'competition_entry' => $this->entity->id(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    // Copy original entry values to this entity for reentry.
    if ($this->entity->isReentry) {
      $existing = $this->competitionManager->getCompetitionEntries($this->entity);
      if (!empty($existing)) {
        // These results are sorted by `created` oldest to newest, so pull the
        // first (original) entry.
        $existing = array_slice($existing, 0, 1);
        $existing = $existing[0];

        $elements = Element::children($form);
        foreach ($elements as $element) {
          if ($existing->hasField($element)) {
            $this->entity->set($element, $existing->get($element)->getValue());
          }
        }

        $this->entity->set('uid', $this->currentUser()->id());
        $this->entity->setReferrerEntry($existing);
      }
    }

    // Save the entry.
    parent::save($form, $form_state);
    $entry = $this->entity;

    $entry
      ->setTempData(array())
      ->save();

    if ($this->currentUser()->id() == $entry->getOwnerId()) {
      $entry
        ->setStatus(CompetitionEntryInterface::STATUS_FINALIZED)
        ->save();

      // Only send email notification in this situation - i.e. entry owner has
      // just made the final submission. (Once entry status == Finalized, owner
      // does not have access to edit it anymore.)
      $entry->sendSubmissionConfirmEmailIfConfigured();

      if ($this->entity->isReentry) {
        drupal_set_message($this->t('Your latest entry to the %cycle %label has been received!', [
          '%cycle' => $this->competition->getCycle(),
          '%label' => $this->competition->label(),
        ]));
      }
    }

    // Send user to read-only entry view.
    $form_state->setRedirect('entity.competition_entry.canonical', [
      'competition_entry' => $entry->id(),
    ]);
  }

}
