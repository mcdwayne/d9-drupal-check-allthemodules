<?php
/**
 * @file
 * Contains \Drupal\byu_academic_calendar\Plugin\Block\AcademicCalendarBlock.
 */
namespace Drupal\byu_academic_calendar\Plugin\Block;
use Drupal\Core\Block\BlockBase;
/**
 * Provides BYU Academic Calendar block.
 *
 * @Block(
 *   id = "academic_calendar_block",
 *   admin_label = @Translation("Academic Calendar Block"),
 *   category = @Translation("Blocks")
 * )
 */
class AcademicCalendarBlock extends BlockBase {

    /**
     * {@inheritdoc}
     */
    public function build() {
        $year = intval(date("Y"));
        $thisMonth = intval(date("m"));


        $ch = curl_init();
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $html = "";
        for($y = $year - 2; $y <= $year + 1; $y++) {
            for($h = 1; $h <= 2; $h++) {
                $url = "https://registrar.byu.edu/returnHTMLCalendar?year={$y}&half={$h}";
                curl_setopt($ch, CURLOPT_URL, $url);
                $json = curl_exec($ch);
                $calArr = json_decode($json, true);
                $calendar = isset($calArr[ "html" ]) ? $calArr[ "html" ] : "<h3>Sorry, the Academic Calendar is currently unavailable</h3>";

                if (isset($calArr[ "html" ])) {
                    $calendar = str_replace("calendar-months clearfix", "calendar-months", $calendar);
                }

                $sidebar = isset($calArr[ "sidebar" ]) ? $calArr[ "sidebar" ] : "<h3>Sorry, the Academic Calendar is currently unavailable</h3>";
                $key = isset($calArr[ "key" ]) ? $calArr[ "key" ] : "<h3>Sorry, the Academic Calendar is currently unavailable</h3>";
                $date = "<div class=\"calendar-controls\"><div class=\"year-text\">{$y}</div><div class=\"calendar-buttons\"><button class=\"calendar-prev-btn\" id=\"{$y}-{$h}-prev-btn\" name=\"previous\"><span class=\"arrow\"><</span><span class='hidden'>previous</span></button><button class=\"calendar-next-btn\" id=\"{$y}-{$h}-next-btn\" name=\"next\"><span class=\"arrow\">></span><span class='hidden'>next</span></button></div></div>";
                $html .= "<div id=\"{$y}-{$h}\" class=\"hidden\">";
                $html .= $sidebar . $date . $calendar . $key;
                $html .= "</div>";
            }
        }
        curl_close($ch);
        $html .= "<a href=\"https://registrar.byu.edu/academic-calendar\" target=\"_blank\" id=\"academic-calendar-btn\">ACADEMIC CALENDAR</a>";

        return [
            '#type' => 'inline_template',
            '#template' => '<div class="academic-calendar">{{ content | raw }}</div>',
            '#context' => [
                'content' => $html
            ],
            '#attached' => [
                'library' => [
                    'byu_academic_calendar/byu_academic_calendar'
                 ]
            ]
        ];
    }
}