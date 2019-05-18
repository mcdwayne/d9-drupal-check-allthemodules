<?php

namespace Drupal\competition\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Url;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\competition\CompetitionJudgingSetup;

/**
 * Defines the Competition Judging Rounds Setup form.
 */
class CompetitionJudgesRoundsSetupForm extends FormBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The competition judging service.
   *
   * @var \Drupal\competition\CompetitionJudgingSetup
   */
  protected $judgingSetup;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\competition\CompetitionJudgingSetup $judging_setup
   *   The competition judging service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, CompetitionJudgingSetup $judging_setup) {

    $this->entityTypeManager = $entity_type_manager;
    $this->judgingSetup = $judging_setup;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('competition.judging_setup')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'competition_judges_rounds_setup';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Since we're implementing FormInterface::buildForm(), we cannot add the
    // expected (and necessary) $competition arg to the method signature -
    // even though it is received by FormBuilder::getForm() and passed in here
    // via FormBuilder::retrieveForm().
    // Instead retrieve it from form state.
    /** @var \Drupal\competition\CompetitionInterface $competition */
    $competition = $form_state->getBuildInfo()['args'][0];
    $form_state->set('competition', $competition);

    $judging = $competition->getJudging();

    $form['wrap'] = [
      '#type' => 'details',
      '#title' => $this->t("Assign Judges to Rounds"),
    ];

    if (empty($judging->rounds)) {
      $form['wrap']['empty'] = [
        '#markup' => $this->t("Please <a href='\":competition_edit\"'>configure rounds</a> in order to assign judges.", [
          ':competition_edit_url' => Url::fromRoute('entity.competition.edit_form', [
            'competition' => $competition->id(),
          ])->toString(),
        ]),
      ];
    }
    else {

      // Define the table render element. The #empty message will appear if
      // there are no judge users.
      $form['wrap']['assignments'] = [
        '#type' => 'table',
        '#header' => [
          $this->t("Judge User"),
          $this->t("Rounds"),
        ],
        // TODO: improve this message.
        '#empty' => $this->t(
          "<p>There are currently no judge user accounts.</p>
          <p>To define judge users:</p>
          <ul>
          <li><a href=\":competition_permissions_url\">Grant the 'Judge competition entries' permission</a> to at least one role.</li>
          <li><a href=\":admin_people_url\">Apply these role(s) to some user accounts.</a></li>
          </ul>",
          [
            ':competition_permissions_url' => Url::fromRoute('user.admin_permissions', [], [
              'fragment' => 'module-competition',
            ])->toString(),
            ':admin_people_url' => Url::fromRoute('entity.user.collection')->toString(),
          ]
        ),
        '#tree' => TRUE,
      ];

      // Retrieve judge users.
      $judge_users = $this->judgingSetup->getJudgeUsers(NULL, TRUE);

      if (!empty($judge_users)) {

        // Get the existing judge-round assignments.
        $defaults = (!empty($judging->judges_rounds) ? $judging->judges_rounds : []);

        // Define the round checkboxes to be added to each judge row.
        $checkboxes_rounds = [
          '#type' => 'checkboxes',
        ];
        foreach (array_keys($judging->rounds) as $rid) {
          $checkboxes_rounds['#options'][$rid] = $this->t("Round @n", ['@n' => $rid]);
        }

        // Add row per judge.
        /** @var \Drupal\user\Entity\User $account */
        foreach ($judge_users as $uid => $account) {
          $form['wrap']['assignments'][$uid] = [];
          $row = &$form['wrap']['assignments'][$uid];

          $row['#attributes']['class'][] = 'form--inline';

          $row['name'] = [
            '#plain_text' => $account->getUsername(),
          ];

          $row['rounds'] = $checkboxes_rounds;
          if (!empty($defaults[$uid])) {
            $row['rounds']['#default_value'] = $defaults[$uid];
          }
        }

        // Add submit button only if there are judges.
        $form['wrap']['submit'] = [
          '#type' => 'submit',
          '#value' => $this->t("Save"),
        ];
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $assignments = [];

    $values = $form_state->getValues();

    /*
     * Structure of $values['assignments']:
     * '7' => [ // judge uid 7
     *   'rounds' => [
     *     '1' => '1', // Round 1 checked
     *     '2' => 0, // Round 2 unchecked
     *   ],
     * ],
     */

    if (!empty($values['assignments'])) {
      foreach ($values['assignments'] as $uid => $row) {
        $rounds = array_filter($row['rounds']);
        if (!empty($rounds)) {
          if (!isset($assignments[$uid])) {
            $assignments[(int) $uid] = [];
          }
          foreach (array_keys($rounds) as $rid) {
            $assignments[(int) $uid][] = (int) $rid;
          }
        }
      }
    }

    // Set assignments in 'judges_roles' key under competition config entity's
    // 'judging' property.
    // Note that other 'judging' settings are configured in CompetitionForm.
    $competition = $form_state->get('competition');
    $judging = (array) $competition->getJudging();
    $judging['judges_rounds'] = $assignments;
    $competition
      ->setJudging($judging)
      ->save();

    // $this->entityTypeManager->clearCachedDefinitions();
    drupal_set_message($this->t("Saved judge assignments to rounds."));

  }

}
