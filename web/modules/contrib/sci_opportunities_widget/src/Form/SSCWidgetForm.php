<?php

namespace Drupal\sscwidget\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements config corm for widget
 */

class SSCWidgetForm extends ConfigFormBase
{

    /**
     * returns ford ID
     */

    public function getFormId() 
    {
        return 'sscwidget_form';
    }

    /**
     * provides form build
     */

    public function buildForm(array $form, FormStateInterface $form_state) 
    {
        $form['items_type'] = [
          '#type' => 'select',
          '#options' => [
            'conferences' => 'Conferences, Symposia, Seminars',
            'grants' => 'Scholarships, Grants and Contests',
            'vacancies' => 'Vacancies',
          ],
          '#required' => true,
          '#empty_option' => '-- please select --',
          '#title' => $this->t('Select opportunity type to display in widget'),
          '#default_value' => $this->config('sscwidget.sscconf')->get('items_type'),
        ];
        $form['grants_type'] = [
          '#type' => 'select',
          '#options' => [
            'all' => $this->t('Display all types'),
            'For studying abroad' => $this->t('Scholarships for studying abroad'),
            'Joint research project' => $this->t('Joint research projects'),
            'For scientific researches' => $this->t('Grants for conducting research'),
            'Summer school' => $this->t('Summer schools'),
            'Internship' => $this->t('Internships'),
            'Educational contest' => $this->t('Educational contests'),
            'Art contest' => $this->t('Art contests'),
            'Social contest' => $this->t('Social contests'),
          ],
          '#states' => ['visible' => [
            'select[name="items_type"]' => ['value' => 'grants'],
          ]],
          '#title' => $this->t('Type of grants to be displayed'),
          '#default_value' => null !== $this->config('sscwidget.sscconf')->get('grants_type') ? $this->config('sscwidget.sscconf')->get('grants_type') : 'all',
        ];
        $form['vacancies_area'] = [
          '#type' => 'select',
          '#options' => [
            'all' => $this->t('Display vacancies from all areas'),
            'for physicists' => $this->t('Vacancies for physicists'),
            'for chemists' => $this->t('Vacancies for chemists'),
            'for mathematicians' => $this->t('Vacancies for mathematicians'),
            'technical specialties' => $this->t('Vacancies technical specialties'),
            'for economists' => $this->t('Vacancies for economists'),
            'for lawyers' => $this->t('Vacancies for lawyers'),
            'for humanities' => $this->t('Vacancies for humanities'),
            'for biologists' => $this->t('Vacancies for biologists'),
            'for physicians and pharmacists' => $this->t('Vacancies for physicians and pharmacists'),
            'for historians' => $this->t('Vacancies for historians'),
            'for surveyors' => $this->t('Vacancies for surveyors'),
            'for ecologists' => $this->t('Vacancies for ecologists'),
          ],
          '#states' => ['visible' => [
            'select[name="items_type"]' => ['value' => 'vacancies'],
          ]],
          '#title' => $this->t('Vacancies from which area to display?'),
          '#default_value' => null !== $this->config('sscwidget.sscconf')->get('vacancies_area') ? $this->config('sscwidget.sscconf')->get('vacancies_area') : 'all',
        ];
        $form['conferences_area'] = [
          '#type' => 'select',
          '#options' => [
            'all' => $this->t('Display conferences from all areas'),
            'Ecology' => $this->t('Ecology'),
            'Nanotech' => $this->t('Nanotech'),
            'Physics and math' => $this->t('Physics and math'),
            'Chemistry' => $this->t('Chemistry'),
            'Biology' => $this->t('Biology'),
            'Geology and mineralogy' => $this->t('Geology and mineralogy'),
            'Technical sciences' => $this->t('Technical sciences'),
            'Agricultural' => $this->t('Agricultural'),
            'History and archeology' => $this->t('History and archeology'),
            'Economics and management' => $this->t('Economics and management'),
            'Philosophy' => $this->t('Philosophy'),
            'Humanities' => $this->t('Humanities'),
            'Geography' => $this->t('Geography'),
            'Law' => $this->t('Law'),
            'Pedagogy' => $this->t('Pedagogy'),
            'Medicine' => $this->t('Medicine'),
            'Pharmaceutics' => $this->t('Pharmaceutics'),
            'Veterinary science' => $this->t('Veterinary science'),
            'Arts' => $this->t('Arts'),
            'Architecture' => $this->t('Architecture'),
            'Psychology' => $this->t('Psychology'),
            'Sociology' => $this->t('Sociology'),
            'Political science' => $this->t('Political science'),
            'Cultural science' => $this->t('Cultural science'),
            'Earth sciences' => $this->t('Earth sciences'),
            'Computer science' => $this->t('Computer science'),
          ],
          '#states' => ['visible' => [
            'select[name="items_type"]' => ['value' => 'conferences'],
          ]],
          '#title' => $this->t('Scientific area of conferences'),
          '#default_value' => null !== $this->config('sscwidget.sscconf')->get('conferences_area') ? $this->config('sscwidget.sscconf')->get('conferences_area') : 'all',
        ];
        $form['items_lang'] = [
          '#type' => 'select',
          '#options' => [
            'en' => 'English',
            'ru' => 'Russian',
            'uk' => 'Ukrainian',
            'all' => 'All languages',
          ],
          '#required' => true,
          '#empty_option' => '-- please select --',
          '#title' => $this->t('Select language of opportunities to display'),
          '#default_value' => $this->config('sscwidget.sscconf')->get('items_lang'),
        ];
        $form['items_quantity'] = [
          '#type' => 'number',
          '#title' => $this->t('Number of items to display in widget'),
          '#step' => 1,
          '#min' => 1,
          '#max' => 15,
          '#required' => true,
          '#default_value' => $this->config('sscwidget.sscconf')->get('items_quantity'),
        ];
        return parent::buildForm($form, $form_state);
    }

    /**
     * provides form validation
     */

    public function validateForm(array &$form, FormStateInterface $form_state) 
    {
        if (!$form_state->isValueEmpty('items_quantity')) {
            if ($form_state->getValue('items_quantity') < 1 || $form_state->getValue('items_quantity') > 15) {
                $form_state->setErrorByName('items_quantity', t('Items quantity should be between 1 and 15!'));
            }
        } else {
            $form_state->setErrorByName('items_quantity', t('Items quantity field can\'t be blank!'));
        }
    }

    /**
     * processes form submission
     */

    public function submitForm(array &$form, FormStateInterface $form_state) 
    {
        parent::submitForm($form, $form_state);
        $this->configFactory->getEditable('sscwidget.sscconf')
            ->set('items_type', $form_state->getValue('items_type'))
            ->set('grants_type', $form_state->getValue('grants_type'))
            ->set('vacancies_area', $form_state->getValue('vacancies_area'))
            ->set('conferences_area', $form_state->getValue('conferences_area'))
            ->set('items_quantity', $form_state->getValue('items_quantity'))
            ->set('items_lang', $form_state->getValue('items_lang'))
            ->save();
        $datacid = 'sscwidget:data';
        \Drupal::cache()->delete($datacid);
        drupal_set_message(t("Widget cache cleared!"));
    }

    /**
     * returns ID of config where form information is stored
     */

    protected function getEditableConfigNames() 
    {
        return ['sscwidget.sscconf'];
    }

}