<?php

use CommerceGuys\Addressing\Subdivision\SubdivisionRepository as BaseSubdivisionRepository;

// Command: 'drush scr <file path>'

class SubdivisionRepository extends BaseSubdivisionRepository {

  // Make the loadDefinitions() public.
  public function loadDefinitions(array $parents) {
    return parent::loadDefinitions($parents);
  }
}

/** @var \CommerceGuys\Addressing\Subdivision\SubdivisionRepositoryInterface $subdivision_repository */
$subdivision_repository = new SubdivisionRepository();
$provinces = $subdivision_repository->getList(['CN'], 'zh-Hans');

$start = time();
print 'Start at ' . $start . PHP_EOL;
//sleep(3);
for ($i = 0; $i < 10000; $i++) {
  get_cites_without_children($subdivision_repository, $provinces);
}
print 'Total Time: ' . (time() - $start) . PHP_EOL;

//print_r(get_provinces_with_depth2($subdivision_repository, $provinces));
// Array
// (
//   [Macau] => 澳门
//   [Beijing Shi] => 北京市
//   [Chongqing Shi] => 重庆市
//   [Shanghai Shi] => 上海市
//   [Tianjin Shi] => 天津市
//   [Hong Kong] => 香港
// )

//print_r(get_cites_without_children($subdivision_repository, $provinces));
// Array
// (
//   [Jiayuguan Shi] => 嘉峪关市
//   [Dongguan Shi] => 东莞市
//   [Zhongshan Shi] => 中山市
//   [Baisha Xian] => 白沙县
//   [Baoting Xian] => 保亭县
//   [Changjiang Xian] => 昌江县
//   [Chengmai Xian] => 澄迈县
//   [Danzhou Shi] => 儋州市
//   [Ding'an Xian] => 定安县
//   [Dongfang Shi] => 东方市
//   [Ledong Xian] => 乐东县
//   [Lingao Xian] => 临高县
//   [Lingshui Xian] => 陵水县
//   [Qionghai Shi] => 琼海市
//   [Qiongzhong Xian] => 琼中县
//   [Sanya Shi] => 三亚市
//   [Tunchang Xian] => 屯昌县
//   [Wanning Shi] => 万宁市
//   [Wenchang Shi] => 文昌市
//   [Wuzhishan Shi] => 五指山市
//   [Qianjiang Shi] => 潜江市
//   [Shennongjia Linqu] => 神农架林区
//   [Tianmen Shi] => 天门市
//   [Xiantao Shi] => 仙桃市
//   [Ala'er Shi] => 阿拉尔市
//   [Shihezi Shi] => 石河子市
//   [Tumushuke Shi] => 图木舒克市
//   [Wujiaqu Shi] => 五家渠市
// )

function get_provinces_with_depth2(SubdivisionRepository $subdivision_repository, array $provinces) {
  $provinces_depth2 = [];
  foreach ($provinces as $code => $name) {
    $definitions = $subdivision_repository->loadDefinitions(['CN', $code]);
    $has_children = FALSE;
    foreach ($definitions['subdivisions'] as $city_code => $definition) {
      if (!empty($definition['has_children'])) {
        $has_children = TRUE;
        break;
      }
    }
    if (!$has_children) {
      $provinces_depth2[$code] = $name;
    }
  }
  return $provinces_depth2;
}

function get_cites_without_children(SubdivisionRepository $subdivision_repository, array $provinces) {
  $provinces_with_depth2 = get_provinces_with_depth2($subdivision_repository, $provinces);
  $cities_without_children = [];
  foreach ($provinces as $code => $name) {
    // Bypass Beijing, HK, Macro, etc which have depth 2.
    if (isset($provinces_with_depth2[$code])) {
      continue;
    }

    $definitions = $subdivision_repository->loadDefinitions(['CN', $code]);
    foreach ($definitions['subdivisions'] as $city_code => $definition) {
      if (empty($definition['has_children'])) {
        $cities_without_children[$city_code] = $definition['local_name'];
      }
    }
  }
  return $cities_without_children;
}