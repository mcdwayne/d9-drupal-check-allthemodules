<?php

namespace Drupal\address_cn;

/**
 * Defines an interface for address cn manager.
 */
interface AddressCnManagerInterface {

  /**
   * Define provinces with preferred order (refer to jd.com) and their
   * subdivision depths (province, city, district).
   *
   * We make an assumption that municipalities like Beijing has subdivision
   * depth 2 (Beijing Shi -> Changping Qu) and general provinces like Guangdong
   * has the depth 3 (Guangdong Sheng -> Shenzhen Shi -> Bao'an Qu), this makes
   * our life easier :)
   *
   * This list refers to
   * vendor/commerceguys/addressing/resources/subdivision/CN.json
   *
   * @see scripts/generate.php::get_provinces_with_depth2()
   */
  const PROVINCES = [
    'Beijing Shi' => 2,             // 北京市
    'Shanghai Shi' => 2,            // 上海市
    'Tianjin Shi' => 2,             // 天津市
    'Chongqing Shi' => 2,           // 重庆市
    'Hebei Sheng' => 3,             // 河北省
    'Shanxi Sheng' => 3,            // 山西省
    'Henan Sheng' => 3,             // 河南省
    'Liaoning Sheng' => 3,          // 辽宁省
    'Jilin Sheng' => 3,             // 吉林省
    'Heilongjiang Sheng' => 3,      // 黑龙江省
    'Neimenggu Zizhiqu' => 3,       // 内蒙古
    'Jiangsu Sheng' => 3,           // 江苏省
    'Shandong Sheng' => 3,          // 山东省
    'Anhui Sheng' => 3,             // 安徽省
    'Zhejiang Sheng' => 3,          // 浙江省
    'Fujian Sheng' => 3,            // 福建省
    'Hubei Sheng' => 3,             // 湖北省
    'Hunan Sheng' => 3,             // 湖南省
    'Guangdong Sheng' => 3,         // 广东省
    'Guangxi Zhuangzuzizhiqu' => 3, // 广西
    'Jiangxi Sheng' => 3,           // 江西省
    'Sichuan Sheng' => 3,           // 四川省
    'Hainan Sheng' => 3,            // 海南省
    'Guizhou Sheng' => 3,           // 贵州省
    'Yunnan Sheng' => 3,            // 云南省
    'Xizang Zizhiqu' => 3,          // 西藏
    'Shaanxi Sheng' => 3,           // 陕西省
    'Gansu Sheng' => 3,             // 甘肃省
    'Qinghai Sheng' => 3,           // 青海省
    'Ningxia Huizuzizhiqu' => 3,    // 宁夏
    'Xinjiang Weiwuerzizhiqu' => 3, // 新疆
    'Hong Kong' => 2,               // 香港
    'Macau' => 2,                   // 澳门
    'Taiwan' => 3,                  // 台湾
  ];

  /**
   * Define exception cities which has no children.
   *
   * @see scripts/generate.php::get_cites_without_children()
   */
  const CITIES = [
    'Jiayuguan Shi',     // 嘉峪关市
    'Dongguan Shi',      // 东莞市
    'Zhongshan Shi',     // 中山市
    'Baisha Xian',       // 白沙县
    'Baoting Xian',      // 保亭县
    'Changjiang Xian',   // 昌江县
    'Chengmai Xian',     // 澄迈县
    'Danzhou Shi',       // 儋州市
    'Ding\'an Xian',     // 定安县
    'Dongfang Shi',      // 东方市
    'Ledong Xian',       // 乐东县
    'Lingao Xian',       // 临高县
    'Lingshui Xian',     // 陵水县
    'Qionghai Shi',      // 琼海市
    'Qiongzhong Xian',   // 琼中县
    'Sanya Shi',         // 三亚市
    'Tunchang Xian',     // 屯昌县
    'Wanning Shi',       // 万宁市
    'Wenchang Shi',      // 文昌市
    'Wuzhishan Shi',     // 五指山市
    'Qianjiang Shi',     // 潜江市
    'Shennongjia Linqu', // 神农架林区
    'Tianmen Shi',       // 天门市
    'Xiantao Shi',       // 仙桃市
    'Ala\'er Shi',       // 阿拉尔市
    'Shihezi Shi',       // 石河子市
    'Tumushuke Shi',     // 图木舒克市
    'Wujiaqu Shi',       // 五家渠市
  ];

  /**
   * Determines whether an address subdivision with given code under given
   * parents has children.
   *
   * @param string $code
   *   The subdivision code, for example, 'Changping Qu'
   * @param array $parents
   *   The parents (country code, subdivision codes), for example,
   *   ['CN', 'Beijing Shi'], ['CN', 'Guangdong Sheng', 'Shenzhen Shi']
   *
   * @return bool
   *   TRUE if the subdivision has children.
   *
   * @see \CommerceGuys\Addressing\Subdivision\SubdivisionRepositoryInterface::getList()
   */
  public function hasChildren($code, array $parents);

  /**
   * Sorts provinces by our preferred order.
   *
   * @param array $provinces
   *   An array of provinces keyed by code.
   *
   * @return bool
   *   TRUE on success or FALSE on failure.
   */
  public function sortProvinces(array &$provinces);

  /**
   * Returns a subdivision by given subdivision code.
   *
   * @param string $code
   *   The subdivision code.
   *
   * @return array
   *   An associative array of subdivision properties.
   */
  public function getSubdivision($code);

}
