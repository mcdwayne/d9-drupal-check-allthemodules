<?php

/**
 * @file
 * Contains Drupal\quran\Form\SurahForm.
 */

namespace Drupal\quran\Form;


use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements the SurahForm form controller.
 *
 * Add selector for quran translation
 *
 * @see \Drupal\Core\Form\FormBase
 */
class SurahForm extends FormBase {

  /**
   * Build the simple form.
   *
   * A build form method constructs an array that defines how markup and
   * other form elements are included in an HTML form.
   *
   * @param array $form
   *   Default form array structure.
   * @param FormStateInterface $form_state
   *   Object containing current form state.
   *
   * @return array
   *   The render array defining the elements of the form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $request = \Drupal::request();
    $lang = $request->query->get('trans');

    if (is_null($lang)) $lang = 'en';

    $form['trans'] = [
      '#title' => $this->t('Translation'),
      '#type' => 'select',
      '#options' => [
        'sq' => 'Albanian: Efendi Nahi',
        'sq-mehdiu' => 'Albanian: Feti Mehdiu',
        'sq-ahmeti' => 'Albanian: Sherif Ahmeti',
        'ber' => 'Amazigh: At Mensur',
        'am' => 'Amharic: ሳዲቅ & ሳኒ ሐቢብ',
        'ar' => 'Arabic: تفسير المیسر',
        'ar-jalalayn' => 'Arabic: تفسير الجلالين',
        'az' => 'Azerbaijani: Məmmədəliyev & Bünyadov',
        'az-musayev' => 'Azerbaijani: Musayev',
        'id' => 'Bahasa Indonesia: Departemen Agama',
        'id-muntakhab' => 'Bahasa Indonesia: Quraish Shihab',
        'id-jalalayn' => 'Bahasa Indonesia: Tafsir Jalalayn',
        'bn' => 'Bengali: মুহিউদ্দীন খান',
        'bn-hoque' => 'Bengali: জহুরুল হক',
        'bs-korkut' => 'Bosnian: Korkut',
        'bs' => 'Bosnian: Mlivo',
        'bg' => 'Bulgarian: Теофанов',
        'zh' => 'Chinese: Ma Jian',
        'zh-majian' => 'Chinese: Ma Jian (Traditional)',
        'cs' => 'Czech: Hrbek',
        'cs-nykl' => 'Czech: Nykl',
        'dv' => 'Divehi: ދިވެހި',
        'nl' => 'Dutch: Keyzer',
        'nl-leemhuis' => 'Dutch: Leemhuis',
        'nl-siregar' => 'Dutch: Siregar',
        'en-ahmedali' => 'English: Ahmed Ali',
        'en-ahmedraza' => 'English: Ahmed Raza Khan',
        'en-arberry' => 'English: Arberry',
        'en-daryabadi' => 'English: Daryabadi',
        'en-hilali' => 'English: Hilali & Khan',
        'en-itani' => 'English: Itani',
        'en-maududi' => 'English: Maududi',
        'en-mubarakpuri' => 'English: Mubarakpuri',
        'en-pickthall' => 'English: Pickthall',
        'en-qarai' => 'English: Qarai',
        'en-qaribullah' => 'English: Qaribullah & Darwish',
        'en-sahih' => 'English: Saheeh International',
        'en-sarwar' => 'English: Sarwar',
        'en-shakir' => 'English: Shakir',
        'en-wahiduddin' => 'English: Wahiduddin Khan',
        'en' => 'English: Yusuf Ali',
        'en-transliteration' => 'English: Transliteration',
        'fr' => 'French: Hamidullah',
        'de' => 'German: Abu Rida',
        'de-bubenheim' => 'German: Bubenheim & Elyas',
        'de-khoury' => 'German: Khoury',
        'de-zaidan' => 'German: Zaidan',
        'ha' => 'Hausa: Gumi',
        'hi' => 'Hindi: फ़ारूक़ ख़ान & नदवी',
        'hi-farooq' => 'Hindi: फ़ारूक़ ख़ान & अहमद',
        'it' => 'Italian: Piccardo',
        'ja' => 'Japanese',
        'ko' => 'Korean',
        'ku' => 'Kurdish: ته‌فسیری ئاسان',
        'ml' => 'Malayalam: അബ്ദുല്‍ ഹമീദ് & പറപ്പൂര്‍',
        'ml-karakunnu' => 'Malayalam: കാരകുന്ന് & എളയാവൂര്',
        'ms' => 'Melayu: Basmeih',
        'no' => 'Norwegian: Einar Berg',
        'fa' => 'Persian: الهی قمشه‌ای',
        'pl' => 'Polish: Bielawskiego',
        'pt' => 'Portuguese: El-Hayek',
        'ro' => 'Romanian: Grigore',
        'ru-osmanov' => 'Russian: Османов',
        'ru-krachkovsky' => 'Russian: Крачковский',
        'ru-kuliev' => 'Russian: Кулиев',
        'ru-abuadel' => 'Russian: Абу Адель',
        'ru' => 'Russian: Аль-Мунтахаб',
        'ru-porokhova' => 'Russian: Порохова',
        'ru-sablukov' => 'Russian: Саблуков',
        'ru-kuliev-alsaadi' => 'Russian: Кулиев + ас-Саади',
        'sd' => 'Sindhi: امروٽي',
        'so' => 'Somali: Abduh',
        'es' => 'Spanish: Bornez',
        'es-cortes' => 'Spanish: Cortes',
        'es-garcia' => 'Spanish: Garcia',
        'sw' => 'Swahili: Al-Barwani',
        'sv' => 'Swedish: Bernström',
        'tg' => 'Tajik: Оятӣ',
        'ta' => 'Tamil: ஜான் டிரஸ்ட்',
        'tt' => 'Tatar: Yakub Ibn Nugman',
        'th' => 'Thai: ภาษาไทย',
        'tr' => 'Turkish: Abdulbakî Gölpınarlı',
        'tr-bulac' => 'Turkish: Alİ Bulaç',
        'tr-transliteration' => 'Turkish: Çeviriyazı',
        'tr-diyanet' => 'Turkish: Diyanet İşleri',
        'tr-vakfi' => 'Turkish: Diyanet Vakfı',
        'tr-yuksel' => 'Turkish: Edip Yüksel',
        'tr-yazir' => 'Turkish: Elmalılı Hamdi Yazır',
        'tr-ozturk' => 'Turkish: Öztürk',
        'tr-yoldirim' => 'Turkish: Suat Yıldırım',
        'tr-ates' => 'Turkish: Süleyman Ateş',
        'ur' => 'Urdu: ابوالاعلی مودودی',
        'ur-kanzuliman' => 'Urdu: احمد رضا خان',
        'ur-ahmedali' => 'Urdu: احمد علی',
        'ur-jalandhry' => 'Urdu: جالندہری',
        'ur-qadri' => 'Urdu: طاہر القادری',
        'ur-jawadi' => 'Urdu: علامہ جوادی',
        'ur-junagarhi' => 'Urdu: محمد جوناگڑھی',
        'ur-najafi' => 'Urdu: محمد حسین نجفی',
        'ug' => 'Uyghur: محمد صالح',
        'uz' => 'Uzbek: Мухаммад Содик',
      ],
      '#default_value' => $lang,
      '#attributes' => array('class' => array('form--inline')),
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    // Add a submit button that handles the submission of the form.
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#attributes' => array('class' => array('form--inline')),
    ];

    return $form;
  }

  /**
   * Getter method for Form ID.
   *
   * The form ID is used in implementations of hook_form_alter() to allow other
   * modules to alter the render array built by this form controller.  it must
   * be unique site wide. It normally starts with the providing module's name.
   *
   * @return string
   *   The unique ID of the form defined by this class.
   */
  public function getFormId() {
    return 'quran_surah_form';
  }

  /**
   * Implements form validation.
   *
   * The validateForm method is the default method called to validate input on
   * a form.
   *
   * @param array $form
   *   The render array of the currently built form.
   * @param FormStateInterface $form_state
   *   Object describing the current state of the form.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * Implements a form submit handler.
   *
   * The submitForm method is the default method called for any submit elements.
   *
   * @param array $form
   *   The render array of the currently built form.
   * @param FormStateInterface $form_state
   *   Object describing the current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $surah = \Drupal::routeMatch()->getParameter('surah');
    
    $form_state->setRedirect('quran_surah',
      array('surah' => $surah),
      array(
        'query' => array(
          'trans' => $form_state->getValue('trans'),
        ),
      )
    );
  }

}