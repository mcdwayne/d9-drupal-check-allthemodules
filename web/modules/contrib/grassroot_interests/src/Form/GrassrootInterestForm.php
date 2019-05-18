<?php

/**
 * @file
 * Contains \Drupal\grassroot_interests\Form\GrassrootInterestForm
 */

namespace Drupal\grassroot_interests\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\grassroot_interests\GrassrootInterestManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;

/**
 * Form for adding / editing keywords by user
 */
Class GrassrootInterestForm extends FormBase {

  /**
   * @var \Drupal\grassroot_interests\GrassrootInterestManagerInterface
   */
  protected $grassrootManager;

  /**
   * Constructs a new GrassrootInterestForm object.
   *
   * @param \Drupal\grassroot_interests\GrassrootInterestManagerInterface $grassroot_manager
   */
  public function __construct(GrassrootInterestManagerInterface $grassroot_manager) {
    $this->grassrootManager = $grassroot_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'grassroot_interest_form';
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('grassroot_interests.grassroot_manager')
    );
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state , $keyword_id = '') {
    // @TODO commenting
    if (!empty($keyword_id) && $this->grassrootManager->checkKeywords($keyword_id)) {
      $edit = $this->grassrootManager->getKeywordsByID($keyword_id);
    }
    elseif (!empty($keyword_id) && !$this->grassrootManager->checkKeywords($keyword_id)) {
      throw new NotFoundHttpException();
    }
    else {
      $edit = array(
        'title' => t('Add Keywords'),
        'kw_title' => NULL,
        'root_url' => NULL,
        'url_id' => NULL,
        'keyword' => NULL,
      );
    }

    \Drupal::moduleHandler()->loadInclude('grassroot_interests', 'inc', '/includes/grassroot_interests');

    $form['#tree'] = TRUE;

    // Add keyword link on edit page
    $form['add_nkw_link'] = array(
      '#markup' => isset($edit['add_nkw_link']) ? $edit['add_nkw_link'] : "",
    );

    $form['description'] = array(
      '#type' => 'item',
      '#title' => t('Add keywords to specialty'),
    );

    // Build the number of name fieldsets indicated by $form_state['num_names'].
    $form['name'] = array(
      '#type' => 'details',
      '#title' => $edit['title'],
      '#open' => TRUE,
    );

    $form['name']['first'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#description' => $this->t("Enter Title to be displayed. (Only plain text)"),
      '#size' => 20,
      '#maxlength' => 20,
      '#required' => TRUE,
      '#default_value' => $edit['kw_title'],
    );

    $form['name']['last'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('URL'),
      '#description' => $this->t('The path for this title. This can be an internal Drupal path such as node/add or an external URL such as http://drupal.org.'),
      '#required' => TRUE,
      '#default_value' => $edit['root_url'],
    );

    $form['name']['keywords'] = array(
      '#type' => 'textarea',
      '#title' => $this->t("Keywords"),
      '#description' => $this->t('Enter one keyword in one line. Note: Title is automatically added as a default keyword.'),
      '#default_value' => isset($edit['keyword']) ? $edit['keyword'] : "",
    );

    $form['url_id'] = array(
      '#type' => 'value',
      '#value' => $edit['url_id']
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => 'Submit',
    );

    // List of keywords with operations
    // Table headers.
    $header = array(
      array(
        'data' => t('Title'),
        'field' => 'kw_title',
      ),
      t('Action'),
    );

    // get the data to show a table
    $query = $this->grassrootManager->getAll()
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('\Drupal\Core\Database\Query\TableSortExtender')
      // @TODO need to define own configuration for this.
      ->limit(\Drupal::config('node.settings')->get('items_per_page'))
      ->orderByHeader($header);
    $all_keywords = $query->execute();

    $rows = array();

    foreach ($all_keywords as $keyword) {
      $row = array();
      $row['title'] = $keyword->kw_title;
      $links = array();
      if (\Drupal::currentUser()->hasPermission('edit search keywords')) {
        $links['edit'] = array(
          'title' => $this->t('Edit'),
          'url' => Url::fromRoute('grassroot_interests.grassroot_edit', array('keyword_id' => $keyword->url_id)),
        );
      }
      if (\Drupal::currentUser()->hasPermission('delete search keywords')) {
        $links['delete'] = array(
          'title' => $this->t('Delete'),
          'url' => Url::fromRoute('grassroot_interests.grassroot_delete', array('keyword_id' => $keyword->url_id, 'title' => $keyword->kw_title)),
        );
      }

      $row['operations'] = array(
        'data' => array(
          '#theme' => 'links',
          '#attributes' => array('class' => array('links', 'inline', 'nowrap')),
          '#links' => $links,
        ),
      );
      $rows[] = $row;
    }

    $form['results'] = array(
      '#prefix' => '<h3>' . $this->t('Grassroot Interest Keywords') . '</h3>',
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No keywords available.'),
      '#weight' => 120,
    );
    $form['pager'] = array('#theme' => 'pager', '#weight' => 121);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $title = $form_state->getValue(array('name', 'first'));
    $root_url = $form_state->getValue(array('name', 'last'));
    $keywords = $form_state->getValue(array('name', 'keywords'));
    $url_id = $form_state->getValue('url_id');

    // if editing we must delete all the keywords first
    if (!empty($url_id)) {
      $this->grassrootManager->deleteKeywords($url_id);
      $operation = 'Updated';
    }
    else {
      // generate the unique url
      $url_id = uniqid("ms", TRUE);
      $operation = 'Added';
    }

    // Concat title with keywords, as title is one of the keywords
    $keywords = $title  . PHP_EOL . $keywords;

    // explode keywords into array
    $keywords = explode(PHP_EOL, $keywords);

    // trim all values and find unique values
    $keywords = array_unique(array_map('trim', $keywords));

    // get the values other than empty :)
    $keywords = array_diff($keywords, array( '' ));

    $data = array();
    // prepare the data array to be stored into database
    $data['title'] = $title;
    $data['root_url'] = $root_url;
    $data['keywords'] = $keywords;
    $data['url_id'] = $url_id;

    // save all valid keywords
    $this->grassrootManager->saveKeywords($data);

    drupal_set_message(t("Keywords @operation.", array('@operation' => $operation)), "status");
    $form_state->setRedirect('grassroot_interests.grassroot_main');
  }
}
