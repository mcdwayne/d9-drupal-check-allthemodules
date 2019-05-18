<?php

namespace Drupal\anonymous_publishing_cl\Form;

use Drupal\comment\Entity\Comment;
use Drupal\Component\Utility\Html;
use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AnonymousPublishingClAdminUnverified extends FormBase {

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
    return 'anonymous_publishing_cl_admin_unverified';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {

    // Build an 'Update options' form.
    $form['options'] = array(
      '#type' => 'details',
      '#title' => $this->t('Update options'),
      '#open' => TRUE,
      '#attributes' => array(
        'class' => array('container-inline')
      ),
    );

    $options = array(
      'ban' => $this->t("Delete item and ban it's IP"),
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
      '#markup' => t("<p>The following table shows the IP-addresses, verification e-mail address used, date posted and title of still <em>unverified</em> anonymous posts. To delete the contents and ban the IP-address by moving to Drupal's <code>{blocked_ips}</code> table, check the box in the corresponding lines and execute the &#8220;Delete item and ban it's IP&#8221; action.</p><p>As an alternative to the Drupal <code>{blocked_ips}</code> table you may instead deny access to unwanted IP-addresses using the appropriate command in the web server access file.</p>")
    ];

    $header = array(
      'title' => array(
        'data' => $this->t('Title'),
      ),
      'type' => array(
        'data' => $this->t('Type'),
      ),
      'ip' => array(
        'data' => $this->t('IP-address'),
      ),
      'email' => array(
        'data' => $this->t('Verification e-mail'),
        'class' => array(RESPONSIVE_PRIORITY_MEDIUM),
      ),
      'when' => array(
        'data' => $this->t('When'),
        'class' => array(RESPONSIVE_PRIORITY_LOW),
        'sort' => 'desc',
      ),
    );

    $options = array();
    $hidden_values = array();

    // Fetch all unverified posts.
    $rows = $this->getAllUnverifiedContents($header);

    foreach ($rows as $row) {

      // Default values:
      $url = NULL;
      $type = $this->t('undefined');
      $titlefield = '';
      $datefield = '';
      // Retrieve the title and date of the comment or node depending.
      if ($row->cid) {
        $comment = Comment::load($row->cid);
        $type = $this->t('comment');
        if ($comment) {
          $datefield = $comment->getCreatedTime();
          $titlefield = !empty($comment->getSubject()) ? $comment->getSubject() : '- empty -';
          $url = $comment->permalink();
        }
        else {
          $titlefield = $this->t('-deleted-');
          $datefield = '';
          $url = NULL;
        }
      }
      else if ($row->nid) {
        $type = $this->t('node');
        $node = Node::load($row->nid);
        if ($node) {
          $datefield = $node->getCreatedTime();
          $titlefield = $node->getTitle();
          $url = $node->toUrl();
        }
        else {
          $titlefield = $this->t('-deleted-');
          $datefield = '';
          $url = NULL;
        }
      }

      $datefield = (!empty($datefield)) ? \Drupal::service("date.formatter")
          ->formatInterval(REQUEST_TIME - $datefield, 1) . ' ' . t('ago') : '-NULL-';

      if ($url) {
        $datatitle = array(
          '#type' => 'link',
          '#title' => $titlefield,
          '#url' => $url,
        );
      }
      else {
        $datatitle = array(
          '#markup' => $titlefield,
        );
      }
      $options[$row->apid] = array(
        'title' => array(
          'data' => $datatitle,
        ),
        'type' => array(
          'data' => array(
            '#markup' => $type,
          ),
        ),
        'ip' => array(
          'data' => array(
            '#markup' => $row->ip,
          ),
        ),
        'email' => array(
          'data' => array(
            '#markup' => $row->email,
          ),
        ),
        'when' => array(
          'data' => array(
            '#markup' => $datefield,
          ),
        ),
      );

      $hidden_values[$row->apid] = array(
        'nid' => $row->nid,
        'cid' => $row->cid,
        'ip' => $row->ip,
      );
    }

    $form['hidden_values'] = array(
      '#type' => 'hidden',
      '#value' => serialize($hidden_values),
    );

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
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $operation = $form_state->getValue('operation');
    $ids = $form_state->getValue('items');
    $hiddens = unserialize($form_state->getValue('hidden_values'));

    $deleted = $moved = 0;
    $ownip = \Drupal::request()->getClientIp();

    foreach ($ids as $id) {
      $hidden = $hiddens[$id];

      if ($operation == 'ban') {

        // Don't block self.
        if ($ownip == $hidden['ip']) {
          drupal_set_message(t("You've tried to ban your own IP (request is ignored)."));
          continue;
        }

        if (!empty($hidden['ip'])) {
          $existp = $this->database->select('blocked_ips')
            ->where('ip = :ip', array(':ip' => $hidden['ip']))
            ->execute()
            ->fetchAssoc();
          if (FALSE == $existp) {
            $res = $this->database->insert('blocked_ips')
              ->fields(['ip' => $hidden['ip']])
              ->execute();
          }
          else {
            $res = TRUE;
          }
          if ($res) {
            $res = $this->database->delete('anonymous_publishing')
              ->condition('apid', $id)
              ->execute();
            $moved++;
          }
        }
        if ($hidden['cid']) {
          Comment::load($hidden['cid'])->delete();
          $deleted++;
        }
        elseif ($hidden['nid']) {
          Node::load($hidden['nid'])->delete();
          $deleted++;
        }
      }
    }
    if ($moved) {
      $msg1 = t('IP-address moved to <code>{blocked_ips}</code>.');
      $msg1 .= ' ';
    }
    else {
      $msg1 = '';
    }
    if ($deleted) {
      $msg2 = t('Spam deleted.');
    }
    else {
      $msg2 = t('No spam could be identified.');
    }
    drupal_set_message($msg1 . $msg2);
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
  protected function getAllUnverifiedContents($header) {
    $query = $this->database->select('anonymous_publishing', 'a');
    $query->fields('a');
    $query->where('a.verified = 0');
    $query->extend('Drupal\Core\Database\Query\TableSortExtender')
      ->orderByHeader($header);
    $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(50);
    $query->range(0, 100);

    return $query->execute()->fetchAll();
  }
}
