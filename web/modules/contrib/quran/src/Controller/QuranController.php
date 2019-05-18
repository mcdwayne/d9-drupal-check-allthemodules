<?php
/**
 * @file
 * Contain \Drupal\quran\Controller\QuranController.
 */

namespace Drupal\quran\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Core\Link;

class QuranController extends ControllerBase {
  const QURAN_FILE = 'quran-uthmani.txt';   // quran file
  const META_DATA_FILE = 'quran-data.xml';   // quran metadata file
  const TRANS_FILE = 'id.indonesian.txt';  // translation file

  protected $surahData;

  public function __construct() {
    $dataItems = [
      "index",
      "start",
      "ayas",
      "name",
      "tname",
      "ename",
      "type",
      "rukus",
    ];

    $quranData = file_get_contents(drupal_get_path('module', 'quran') . '/src/' . self::META_DATA_FILE);

    $parser = xml_parser_create();
    xml_parse_into_struct($parser, $quranData, $values, $index);
    xml_parser_free($parser);

    $rows = [];

    $surahData = array();

    for ($i = 1; $i <= 114; $i++) {
      $j = $index['SURA'][$i - 1];
      foreach ($dataItems as $item) {
        $surahData[$i][$item] = $values[$j]['attributes'][strtoupper($item)];
      }

    }
    $this->surahData = $surahData;
  }

  function getSurahData($surah, $property) {
    return $this->surahData[$surah][$property];
  }

  function getSurahAllData($surah) {
    return $this->surahData[$surah];
  }

  function getSurahContents($surah) {
    $startAya = $this->getSurahData($surah, 'start');
    $endAya = $startAya + $this->getSurahData($surah, 'ayas');
    $quranFile = drupal_get_path('module', 'quran') . '/src/' . self::QURAN_FILE;
    $quran = file($quranFile);
    $text = array_slice($quran, $startAya, $endAya - $startAya);
    return $text;
  }

  function getSurahTrans($surah, $lang) {
    $langFile = [
      'am' => 'am.sadiq.txt',
      'ar' => 'ar.muyassar.txt',
      'ar-jalalayn' => 'ar.jalalayn.txt',
      'az' => 'az.mammadaliyev.txt',
      'az-musayev' => 'az.musayev.txt',
      'en' => 'en.yusufali.txt',
      'en-ahmedali' => 'en.ahmedali.txt',
      'en-ahmedraza' => 'en.ahmedraza.txt',
      'en-arberry' => 'en.arberry.txt',
      'en-daryabadi' => 'en.daryabadi.txt',
      'en-hilali' => 'en.hilali.txt',
      'en-itani' => 'en.itani.txt',
      'en-maududi' => 'en.maududi.txt',
      'en-mubarakpuri' => 'en.mubarakpuri.txt',
      'en-pickthall' => 'en.pickthall.txt',
      'en-qarai' => 'en.qarai.txt',
      'en-qaribullah' => 'en.qaribullah.txt',
      'en-sahih' => 'en.sahih.txt',
      'en-sarwar' => 'en.sarwar.txt',
      'en-shakir' => 'en.shakir.txt',
      'en-transliteration' => 'en.transliteration.txt',
      'en-wahiduddin' => 'en.wahiduddin.txt',
      'es' => 'es.bornez.txt',
      'es-cortes' => 'es.cortes.txt',
      'es-garcia' => 'es.garcia.txt',
      'ber' => 'ber.mensur.txt',
      'bn' => 'bn.bengali.txt',
      'bn-hoque' => 'bn.hoque.txt',
      'bs' => 'bs.mlivo.txt',
      'bg' => 'bg.theophanov.txt',
      'cs' => 'cs.hrbek.txt',
      'cs-nykl' => 'cs.nykl.txt',
      'de' => 'de.aburida.txt',
      'de-bubenheim' => 'de.bubenheim.txt',
      'de-khoury' => 'de.khoury.txt',
      'de-zaidan' => 'de.zaidan.txt',
      'dv' => 'dv.divehi.txt',
      'ha' => 'ha.gumi.txt',
      'hi' => 'hi.hindi.txt',
      'hi-farooq' => 'hi.farooq.txt',
      'id' => 'id.indonesian.txt',
      'id-jalalayn' => 'id.jalalayn.txt',
      'id-muntakhab' => 'id.muntakhab.txt',
      'fa' => 'fa.ghomshei.txt',
      'fr' => 'fr.hamidullah.txt',
      'it' => 'it.piccardo.txt',
      'ja' => 'ja.japanese.txt',
      'ko' => 'ko.korean.txt',
      'ku' => 'ku.asan.txt',
      'ml' => 'ml.abdulhameed.txt',
      'ml-karakunnu' => 'ml.karakunnu.txt',
      'ms' => 'ms.basmeih.txt',
      'nl' => 'nl.keyzer.txt',
      'nl-leemhuis' => 'nl.leemhuis.txt',
      'nl-siregar' => 'nl.siregar.txt',
      'no' => 'no.berg.txt',
      'pl' => 'pl.bielawskiego.txt',
      'pt' => 'pt.elhayek.txt',
      'ro' => 'ro.grigore.txt',
      'ru' => 'ru.muntahab.txt',
      'ru-abuadel' => 'ru.abuadel.txt',
      'ru-krachkovsky' => 'ru.krachkovsky.txt',
      'ru-kuliev' => 'ru.kuliev.txt',
      'ru-kuliev-alsaadi' => 'ru.kuliev-alsaadi.txt',
      'ru-osmanov' => 'ru.osmanov.txt',
      'ru-porokhova' => 'ru.porokhova.txt',
      'ru-sablukov' => 'ru.sablukov.txt',
      'sd' => 'sd.amroti.txt',
      'so' => 'so.abduh.txt',
      'sq' => 'sq.nahi.txt',
      'sq-ahmeti' => 'sq.ahmeti.txt',
      'sq-mehdiu' => 'sq.mehdiu.txt',
      'sw' => 'sw.barwani.txt',
      'sv' => 'sv.bernstrom.txt',
      'ta' => 'ta.tamil.txt',
      'tg' => 'tg.ayati.txt',
      'th' => 'th.thai.txt',
      'tr' => 'tr.golpinarli.txt',
      'tr-ates' => 'tr.ates.txt',
      'tr-bulac' => 'tr.bulac.txt',
      'tr-diyanet' => 'tr.diyanet.txt',
      'tr-ozturk' => 'tr.ozturk.txt',
      'tr-transliteration' => 'tr.transliteration.txt',
      'tr-vakfi' => 'tr.vakfi.txt',
      'tr-yazir' => 'tr.yazir.txt',
      'tr-yildirim' => 'tr.yildirim.txt',
      'tr-yuksel' => 'tr.yuksel.txt',
      'tt' => 'tt.nugman.txt',
      'ug' => 'ug.saleh.txt',
      'ur' => 'ur.maududi.txt',
      'ur-ahmedali' => 'ur.ahmedali.txt',
      'ur-jalandhry' => 'ur.jalandhry.txt',
      'ur-jawadi' => 'ur.jawadi.txt',
      'ur-junagarhi' => 'ur.junagarhi.txt',
      'ur-kanzuliman' => 'ur.kanzuliman.txt',
      'ur-najafi' => 'ur.najafi.txt',
      'ur-qadri' => 'ur.qadri.txt',
      'uz' => 'uz.sodik.txt',
      'zh' => 'zh.jian.txt',
      'zh-majian' => 'zh.majian.txt',
    ];

    if (!array_key_exists($lang, $langFile)) {
      $lang = 'en';
    }

    $transText = $langFile[$lang];

    $startAya = $this->getSurahData($surah, 'start');
    $endAya = $startAya + $this->getSurahData($surah, 'ayas');
    $quranFile = drupal_get_path('module', 'quran') . '/src/' . $transText;
    $quran = file($quranFile);
    $text = array_slice($quran, $startAya, $endAya - $startAya);
    return $text;
  }

  public function content() {

    foreach ($this->surahData as $index => $data) {
      // Normally we would add some nice formatting to our rows
      // but for our purpose we are simply going to add our row
      // to the array.
      $url = Url::fromRoute('quran_surah', ['surah' => $index]);
      $rows[] = [
        $index,
        Link::fromTextAndUrl($data['tname'] . ' (' . $data['ename'] . ')', $url),
        $data['ayas'],
        $data['type'],
      ];
    }

    $table = [
      '#theme' => 'table',
      '#header' => [
        $this->t('No.'),
        $this->t('Surah'),
        $this->t('Verses'),
        $this->t('Place of Revelation'),
      ],
      '#rows' => $rows,
    ];

    return [
      '#theme' => 'table',
      '#header' => [
        $this->t('No.'),
        $this->t('Surah'),
        $this->t('Verses'),
        $this->t('Place of Revelation'),
      ],
      '#rows' => $rows,
    ];
  }

  public function surah($surah) {
    $request = \Drupal::request();
    $lang = $request->query->get('trans');

    if (is_null($lang)) $lang = 'en';

    $surahText = $this->getSurahContents($surah);
    $transText = $this->getSurahTrans($surah, $lang);

    $data = $this->getSurahAllData($surah);

    $showBismillah = FALSE; // change to true to show Bismillahs
    $ayaNum = 0;

    foreach ($surahText as $index => $aya) {
      // remove bismillahs, except for suras 1 and 9
      if (!$showBismillah && $ayaNum == 0 && $surah != 1 && $surah != 9) {
        $aya = preg_replace('/^(([^ ]+ ){4})/u', '', $aya);
      }
      $rows[$ayaNum]['aya'] = $index + 1;
      $rows[$ayaNum]['text']['data'] = $aya;
      $rows[$ayaNum]['text']['class'] = 'quran-text';
      $ayaNum++;
      $rows[$ayaNum]['aya'] = '';
      $rows[$ayaNum]['text']['data']['#markup'] = $transText[$index];
      $rows[$ayaNum]['text']['class'] = 'trans-text';
      //print_r($transText[$index]);
      $ayaNum++;
    }

    $build['#change_form'] = \Drupal::formBuilder()
      ->getForm('Drupal\quran\Form\SurahForm');

    $build['#surah_content'] = [
      '#theme' => 'table',
      '#rows' => $rows,
    ];

    $build['#data'] = $data;
    $build['#lang'] = $lang;

    $build['#attached']['library'][] = 'quran/quran.surah';
    $build['#theme'] = 'quran_surah';

    return $build;

  }
}