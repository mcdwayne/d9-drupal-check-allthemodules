<?php

namespace Drupal\Tests\calendar_link\Kernel;

use Drupal\calendar_link\Twig\CalendarLinkTwigExtension;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests Twig extensions.
 *
 * @group calendar_link
 */
class CalendarLinkTwigExtensionTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['calendar_link'];

  /**
   * Tests that Twig extension loads appropriately.
   */
  public function testTwigExtensionLoaded() {
    $twig_service = \Drupal::service('twig');
    $extension = $twig_service->getExtension(CalendarLinkTwigExtension::class);
    $this->assertEquals(
      get_class($extension),
      CalendarLinkTwigExtension::class,
      'Calendar Link extension loaded successfully.'
    );
  }

  /**
   * Tests that the Twig extension functions are registered properly.
   */
  public function testFunctionsRegistered() {
    /** @var \Twig_SimpleFunction[] $functions */
    $registered_functions = \Drupal::service('twig')
      ->getFunctions();

    $functions = ['calendar_link', 'calendar_links'];

    foreach ($functions as $name) {
      $function = $registered_functions[$name];
      $this->assertTrue($function instanceof \Twig_SimpleFunction);
      $this->assertEquals($function->getName(), $name);
      is_callable($function->getCallable(), TRUE, $callable);
    }
  }

  /**
   * Tests the "calendar_link" Twig function.
   */
  public function testCalendarLinkFunction() {
    $template = "{% set startDate = date('2019-02-24 10:00', 'Etc/UTC') %}{% set endDate = date('2019-02-24 12:00', 'Etc/UTC') %}{% set link = calendar_link('ics', 'title', startDate, endDate, false, 'description', 'location') %}<a href=\"{{ link }}\">Add to calendar</a>";
    $expected_template_output = '<a href="data:text/calendar;charset=utf8,BEGIN:VCALENDAR%0d%0aVERSION:2.0%0d%0aBEGIN:VEVENT%0d%0aUID:a78d4c3cc707c4cde760bad3fbff8ea1%0d%0aSUMMARY:title%0d%0aDTSTART;TZID=Etc/UTC:20190223T230000%0d%0aDTEND;TZID=Etc/UTC:20190224T010000%0d%0aDESCRIPTION:description%0d%0aLOCATION:location%0d%0aEND:VEVENT%0d%0aEND:VCALENDAR">Add to calendar</a>';

    /** @var \Drupal\Core\Template\TwigEnvironment $environment */
    $environment = \Drupal::service('twig');

    $output = (string) $environment->renderInline($template);
    $this->assertEquals($expected_template_output, $output);
  }

  /**
   * Tests the "calendar_links" Twig function.
   */
  public function testCalendarLinksFunction() {
    $template = "{% set startDate = date('2019-02-24 10:00', 'Etc/UTC') %}{% set endDate = date('2019-02-24 12:00', 'Etc/UTC') %}{% set links = calendar_links('title', startDate, endDate, false, 'description', 'address') %}{% for link in links %}<a href=\"{{ link.url }}\" class=\"calendar-type-{{ link.type_key }}\">Add to {{ link.type_name }}</a>{% endfor %}";
    $expected_template_output = '<a href="https://calendar.google.com/calendar/render?action=TEMPLATE&amp;text=title&amp;dates=20190223T230000/20190224T010000&amp;ctz=Etc/UTC&amp;details=description&amp;location=address&amp;sprop=&amp;sprop=name:" class="calendar-type-google">Add to Google</a><a href="data:text/calendar;charset=utf8,BEGIN:VCALENDAR%0d%0aVERSION:2.0%0d%0aBEGIN:VEVENT%0d%0aUID:87b8e999e653acdfff7f6c782d4aa90e%0d%0aSUMMARY:title%0d%0aDTSTART;TZID=Etc/UTC:20190223T230000%0d%0aDTEND;TZID=Etc/UTC:20190224T010000%0d%0aDESCRIPTION:description%0d%0aLOCATION:address%0d%0aEND:VEVENT%0d%0aEND:VCALENDAR" class="calendar-type-ics">Add to iCal</a><a href="https://calendar.yahoo.com/?v=60&amp;view=d&amp;type=20&amp;title=title&amp;st=20190223T230000Z&amp;et=20190224T010000Z&amp;desc=description&amp;in_loc=address" class="calendar-type-yahoo">Add to Yahoo!</a><a href="https://outlook.live.com/owa/?path=/calendar/action/compose&amp;rru=addevent&amp;startdt=20190223T230000&amp;enddt=20190224T010000&amp;subject=title&amp;body=description&amp;location=address" class="calendar-type-webOutlook">Add to Outlook.com</a>';

    /** @var \Drupal\Core\Template\TwigEnvironment $environment */
    $environment = \Drupal::service('twig');

    $output = (string) $environment->renderInline($template);
    $this->assertEquals($expected_template_output, $output);
  }

}
