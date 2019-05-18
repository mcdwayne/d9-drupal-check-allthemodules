<?php

namespace Drupal\aladhan_prayer_times\Plugin\Block;

$localAutoloader = realpath(__DIR__ . '/../../../vendor/autoload.php');
require_once($localAutoloader);

use Drupal\Core\Block\BlockBase;

/**
 *  Provides a Prayer Times Block
 *  
 *   @Block(
 *   id = "aladhan_block",
 *    admin_label = @Translation("AlAdhan Block"),
 *   )
 * */
class AlAdhanBlock extends BlockBase {
    /**
     *    {@inheritdoc}
     **/
    public function build() {
        $config = \Drupal::config('aladhan.config');
        $t = new \AlAdhanApi\TimesByAddress($config->get('location'), null, $config->get('method'), $config->get('latitude_adjustment_method'), $config->get('school'));
        $times = $t->get();
        if ($config->get('display_orientation') == '0') { // Horizontal
            foreach ($times['data']['timings'] as $salat => $time) {
                if (!in_array($salat, ['Sunrise', 'Sunset', 'Imsak', 'Midnight'])) {
                    $columns[] = $time;
                }

            }
            $table = [
                '#type' => 'table',
                '#caption' => $this->t('Prayer Times Today'),
                '#header' => [$this->t('Fajr'), $this->t('Zhuhr'), $this->t('Asr'), $this->t('Maghrib'), $this->t('Isha')],
                '#rows' => [$columns]
                ];

        } else { // Vertical
            foreach ($times['data']['timings'] as $salat => $time) {
                if (!in_array($salat, ['Sunrise', 'Sunset', 'Imsak', 'Midnight'])) {
                    $rows[] = [str_replace('Dhuhr', 'Zhuhr', $salat), $time];
                }
                $table = [
                    '#type' => 'table',
                    '#caption' => $this->t('Prayer Times Today'),
                    '#header' => [$this->t('Prayer'), $this->t('Time')],
                    '#rows' =>$rows
                    ];
            }
        }

        return $table;

    }
}
