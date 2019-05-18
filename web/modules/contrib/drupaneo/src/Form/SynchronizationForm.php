<?php


namespace Drupal\drupaneo\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\drupaneo\Event\SynchronizationEvent;
use Drupal\drupaneo\Service\AkeneoService;

/**
 * Drupaneo synchronization form.
 */
class SynchronizationForm extends FormBase {

    public function getFormId() {
        return 'drupaneo_synchronization';
    }

    public function buildForm(array $form, FormStateInterface $form_state) {
        $form['submit'] = array(
            '#type' => 'submit',
            '#value' => $this->t('Synchronize Akeneo products'),
        );
        return $form;
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {

        $operations = array();

        $limit = 10;

        try {
            $result = self::fetch(1, $limit, 'true');

            $pages = ceil($result->items_count / $limit);

            for ($page = 2; $page <= $pages; $page++) {
                $operations[] = array('\Drupal\drupaneo\Form\SynchronizationForm::fetch', array($page, $limit, 'false'));
            }

            batch_set(array(
                'title' => t('Synchronizing data...'),
                'operations' => $operations,
                'finished' => '\Drupal\drupaneo\Form\SynchronizationForm::finished',
            ));
        }
        catch(\Exception $e) {
            $form_state->setRebuild();
        }
    }

    static function fetch($page, $limit, $with_count = 'false') {

        /* @var AkeneoService $akeneo */
        $akeneo = \Drupal::service('drupaneo.akeneo');

        $dispatcher = \Drupal::service('event_dispatcher');

        try {
            $result = $akeneo->getProducts($page, $limit, $with_count);

            if (isset($result->_embedded) && isset($result->_embedded->items)) {
                foreach ($result->_embedded->items as $product) {
                    // TODO tranform product to pivot
                    $dispatcher->dispatch(SynchronizationEvent::SYNCHRONIZED, new SynchronizationEvent($product));
                }
            }
            return $result;
        }
        catch(\Exception $e) {
            drupal_set_message($e->getMessage(), 'error');
            throw $e;
        }
    }

    static function finished($success, $results) {
        if ($success) {
            drupal_set_message(t('Akeneo products were successfully synchronized.'));
        }
        else {
            drupal_set_message(t('Cannot synchronize products.'), 'error');
        }
    }
}
