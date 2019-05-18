<?php

namespace Drupal\chinese_identity_card\Plugin\ChineseIdentityCardValidator;

use Drupal\chinese_identity_card\Plugin\ChineseIdentityCardValidatorBase;

/**
 * Default validator
 *
 * @ChineseIdentityCardValidator(
 *  id = "default_chinese_identity_card_validator",
 *  description = @Translation("Default validator"),
 * )
 */
class DefaultChineseIdentityCardValidator extends ChineseIdentityCardValidatorBase {

  /**
   * @inheritdoc
   */
  public function validate($chinese_identity_card) {

    $city = array(
      11 => "北京",
      12 => "天津",
      13 => "河北",
      14 => "山西",
      15 => "内蒙古",
      21 => "辽宁",
      22 => "吉林",
      23 => "黑龙江",
      31 => "上海",
      32 => "江苏",
      33 => "浙江",
      34 => "安徽",
      35 => "福建",
      36 => "江西",
      37 => "山东",
      41 => "河南",
      42 => "湖北",
      43 => "湖南",
      44 => "广东",
      45 => "广西",
      46 => "海南",
      50 => "重庆",
      51 => "四川",
      52 => "贵州",
      53 => "云南",
      54 => "西藏",
      61 => "陕西",
      62 => "甘肃",
      63 => "青海",
      64 => "宁夏",
      65 => "新疆",
      71 => "台湾",
      81 => "香港",
      82 => "澳门",
      91 => "国外",
    );
    $id_card_length = strlen($chinese_identity_card);

    // Length checking.
    if (!preg_match('/^\d{17}(\d|x)$/i', $chinese_identity_card) and !preg_match('/^\d{15}$/i', $chinese_identity_card)) {
      return FALSE;
    }

    // Area checking.
    $city_code = array_keys($city);
    if (!in_array(intval(substr($chinese_identity_card, 0, 2)), $city_code)) {
      return FALSE;
    }

    // 15bits card checks the birthday. and convert 18bits.
    if ($id_card_length == 15) {
      $year = '19' . substr($chinese_identity_card, 6, 2);
      $month = substr($chinese_identity_card, 8, 2);
      $day = substr($chinese_identity_card, 10, 2);
      if (!checkdate($month, $day, $year)) {
        return FALSE;
      }
      $s_birthday = $year . '-' . $month . '-' . $day;

      $d = new DateTime($s_birthday);
      $dd = $d->format('Y-m-d');
      if ($s_birthday != $dd) {
        return FALSE;
      }
      // 15 to 18.
      $chinese_identity_card = substr($chinese_identity_card, 0, 6) . "19" . substr($chinese_identity_card, 6, 9);
      // Calculate the checksum of 18bits card.
      $bit_18 = chinese_identity_card_get_verify_bit($chinese_identity_card);
      $chinese_identity_card = $chinese_identity_card . $bit_18;
    }
    // Checking whether the year bigger than 2078, and less than 1900.
    $year = substr($chinese_identity_card, 6, 4);
    if ($year < 1900 || $year > 2078) {
      return FALSE;
    }

    // Handle 18bit card.
    $s_birthday = substr($chinese_identity_card, 6, 4) . '-' . substr($chinese_identity_card, 10, 2) . '-' . substr($chinese_identity_card, 12, 2);
    $d = new DateTime($s_birthday);
    $dd = $d->format('Y-m-d');
    if ($s_birthday != $dd) {
      return FALSE;
    }

    // Checking chinese identity card standard.
    $chinese_identity_card_base = substr($chinese_identity_card, 0, 17);
    if (strtoupper(substr($chinese_identity_card, 17, 1)) != chinese_identity_card_get_verify_bit($chinese_identity_card_base)) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Verify bit
   *
   * @param $chinese_identity_card_base
   *
   * @return bool|mixed
   */
  function chinese_identity_card_get_verify_bit($chinese_identity_card_base) {
    if (strlen($chinese_identity_card_base) != 17) {
      return FALSE;
    }
    // Weighting factor.
    $factor = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);
    // Check code corresponding to the value.
    $verify_number_list = array(
      '1',
      '0',
      'X',
      '9',
      '8',
      '7',
      '6',
      '5',
      '4',
      '3',
      '2',
    );
    $checksum = 0;
    for ($i = 0; $i < strlen($chinese_identity_card_base); $i++) {
      $checksum += substr($chinese_identity_card_base, $i, 1) * $factor[$i];
    }
    $mod = $checksum % 11;
    $verify_number = $verify_number_list[$mod];

    return $verify_number;
  }
}