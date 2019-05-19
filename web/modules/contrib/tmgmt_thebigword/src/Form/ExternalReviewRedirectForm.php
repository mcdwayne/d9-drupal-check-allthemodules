<?php

namespace Drupal\tmgmt_thebigword\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\tmgmt\Entity\RemoteMapping;
use Drupal\tmgmt\JobItemInterface;
use Drupal\tmgmt\RemoteMappingInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * External redirect review form.
 */
class ExternalReviewRedirectForm extends FormBase {

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('datetime.time'));
  }

  /**
   * ExternalReviewRedirectForm constructor.
   *
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(TimeInterface $time) {
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tmgmt_thebigword_external_review_redirect_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, JobItemInterface $tmgmt_job_item = NULL) {

    $mappings = RemoteMapping::loadByLocalData($tmgmt_job_item->getJobId(), $tmgmt_job_item->id());
    $mapping = reset($mappings);

    $task_id = $mapping->getRemoteData('TaskId');
    $endpoint = $mapping->getRemoteData('DirectAccessEndPoint');
    $endpoint = str_replace('{0}', $task_id, $endpoint);

    $form['#action'] = $endpoint;

    $form['#attached'] = [
      'library' => [
        'tmgmt_thebigword/redirect-autosubmit',
      ],
    ];

    $form['description'] = [
      '#markup' => $this->t('Automatically redirecting to Review tool.')
    ];

    // Add the JWT token.
    $form['token'] = [
      '#type' => 'hidden',
      '#value' => $this->buildJwtToken($tmgmt_job_item, $mapping),
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Continue to Review Tool'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Form redirects to external page.
  }

  /**
   * Builds the JWT token for external authentication.
   *
   * @param \Drupal\tmgmt\JobItemInterface $tmgmt_job_item
   *   The job item.
   * @param \Drupal\tmgmt\RemoteMappingInterface $mapping
   *   The remote mapping.
   *
   * @return string
   *   The generated and encoded JWT token.
   */
  protected function buildJwtToken(JobItemInterface $tmgmt_job_item, RemoteMappingInterface $mapping) {
    $header = ['alg' => 'HS256', 'typ' => 'JWT'];
    $payload = [
      'DirectAccessToken' => $mapping->getRemoteData('DirectAccessToken'),
      'iat' => $this->time->getRequestTime(),
      'exp' => $this->time->getRequestTime() + 3600,
    ];

    $settings = NestedArray::mergeDeep(
      $tmgmt_job_item->getTranslatorPlugin()
        ->defaultSettings(), $tmgmt_job_item->getTranslator()->getSettings()
    );
    if (!empty($settings['user_information_control']['review'])) {
      $payload['UserName'] = $this->currentUser()->getAccountName();
      $payload['UserEmail'] = $this->currentUser()->getEmail();
    }

    // Create the unsigned token with the header and payload, make sure that the
    // base64 encoded string is URL safe.
    $unsigned_token = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($header)) . '.' . base64_encode(json_encode($payload)));

    $secret = $mapping->getRemoteData('DirectAccessSharedSecret');

    return $unsigned_token . '.' . Crypt::hmacBase64($unsigned_token, $secret);
  }

}
