<?php

namespace Drupal\anonymous_publishing_cl\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AnonymousPublishingClAdminBlocked extends FormBase {

  /**
   * The database connection service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('database'));
  }

  /**
   * Constructs a \Drupal\anonymous_publishing_cl\Form\AnonymousPublishingClAdminModeration object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection service.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'anonymous_publishing_cl_admin_blocked';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {

    // Build an 'Update options' form.
    $form['options'] = array(
      '#type' => 'details',
      '#title' => $this->t('Update options'),
      '#open' => TRUE,
      '#attributes' => array('class' => array('container-inline')),
    );

    $options = array(
      'block' => $this->t("Block the email address"),
      'unblock' => $this->t("Unblock the email address"),
    );
    $form['options']['operation'] = array(
      '#type' => 'select',
      '#title' => $this->t('Action'),
      '#title_display' => 'invisible',
      '#options' => $options,
      '#default_value' => 'publish',
    );
    $form['options']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Update'),
    );

    $form['apu_info'] = [
      '#markup' => t("<p>The table below shows the e-mail address used to verify, IP-address, generated alias, blocked status for all <em>verified</em> e-mail addresses. To block or unblock some email adresses, check each corresponding line's below and execute the desired action.</p><p>Note than an e-mail address is not listed here until it has been verified.  For yet unverified addresses, see the <em>unverified</em> tab.</p>")
    ];

    $header = array(
      'email' => array(
        'data' => $this->t('Verification email'),
      ),
      'ip' => array(
        'data' => $this->t('IP-address'),
      ),
      'alias' => array(
        'data' => $this->t('Byline (if available)'),
        'class' => array(RESPONSIVE_PRIORITY_MEDIUM),
      ),
    );

    $options = array();

    // Fetch all emails.
    $rows = $this->getAllBlockedContents();

    // Build the table.
    foreach ($rows as $row) {

      $options[$row->auid] = array(
        'email' => array(
          'data' => array(
            '#markup' => Html::escape($row->email),
          ),
        ),
        'ip' => array(
          'data' => array(
            '#markup' => $row->ipaddress,
          ),
        ),
        'alias' => array(
          'data' => array(
            '#markup' => !empty($row->alias) ? Html::escape($row->alias) : $this->t('- none -'),
          ),
        ),
      );
    }

    $form['items'] = array(
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $options,
      '#empty' => $this->t('There is no unverified content.'),
    );

    $form['pager'] = array('#type' => 'pager');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $form_state->setValue('items', array_diff($form_state->getValue('items'), array(0)));
    // We can't execute any 'Update options' if no items were selected.
    if (count($form_state->getValue('items')) == 0) {
      $form_state->setErrorByName('', $this->t('Select one or more items to perform the update on.'));

    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $operation = $form_state->getValue('operation');
    $ids = $form_state->getValue('items');

    $blocked = FALSE;
    foreach ($ids as $id) {

      if ($operation == 'block') {
        $blocked = TRUE;
      }

      $this->database->update('anonymous_publishing_emails')
        ->fields('anonymous_publishing_emails', array('blocked' => $blocked))
        ->condition('auid', $id)
        ->execute();

    }
    drupal_set_message($this->t('The update has been performed.'));
  }

  /**
   * Get all contents to moderate.
   *
   * @param int $test_id
   *   The test_id to retrieve results of.
   *
   * @return array
   *  Array of results grouped by test_class.
   */
  protected function getAllBlockedContents() {
    $query = $this->database->select('anonymous_publishing_emails', 'e');
    $query->fields('e');
    $query->orderBy('e.auid');

    return $query->execute()->fetchAll();
  }

}
