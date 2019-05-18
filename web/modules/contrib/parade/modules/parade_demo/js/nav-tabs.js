(function (exports) {
'use strict';

/**
 * @file
 * Responsive navigation tabs.
 *
 * This also supports collapsible navigable is the 'is-collapsible' class is
 * added to the main element, and a target element is included.
 */

(function ($, Drupal) {

  function init(i, tab) {
    var $tab = $(tab);
    var $target = $tab.find('[data-drupal-nav-tabs-target]');
    var isCollapsible = $tab.hasClass('is-collapsible');

    function openMenu(e) {
      $target.toggleClass('is-open');
    }

    function handleResize(e) {
      $tab.addClass('is-horizontal');
      var $tabs = $tab.find('.tabs');
      var isHorizontal = $tabs.outerHeight() <= $tabs.find('.tabs__tab').outerHeight();
      $tab.toggleClass('is-horizontal', isHorizontal);
      if (isCollapsible) {
        $tab.toggleClass('is-collapse-enabled', !isHorizontal);
      }
      if (isHorizontal) {
        $target.removeClass('is-open');
      }
    }

    $tab.addClass('position-container is-horizontal-enabled');

    $tab.on('click.tabs', '[data-drupal-nav-tabs-trigger]', openMenu);
    $(window).on('resize.tabs', Drupal.debounce(handleResize, 150)).trigger('resize.tabs');
  }

  /**
   * Initialise the tabs JS.
   */
  Drupal.behaviors.navTabs = {
    attach: function (context, settings) {
      var $tabs = $(context).find('[data-drupal-nav-tabs]');
      if ($tabs.length) {
        var notSmartPhone = window.matchMedia('(min-width: 300px)');
        if (notSmartPhone.matches) {
          $tabs.once('nav-tabs').each(init);
        }
      }
    }
  };

})(jQuery, Drupal);

}((this.LaravelElixirBundle = this.LaravelElixirBundle || {})));
//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjpudWxsLCJzb3VyY2VzIjpbIkQ6L2RldmRlc2t0b3AvdGNzLmxvYy93ZWIvdGhlbWVzL3RpZXRvX2FkbWluL3NyYy9zY3JpcHRzL25hdi10YWJzLmpzIl0sInNvdXJjZXNDb250ZW50IjpbIi8qKlxuICogQGZpbGVcbiAqIFJlc3BvbnNpdmUgbmF2aWdhdGlvbiB0YWJzLlxuICpcbiAqIFRoaXMgYWxzbyBzdXBwb3J0cyBjb2xsYXBzaWJsZSBuYXZpZ2FibGUgaXMgdGhlICdpcy1jb2xsYXBzaWJsZScgY2xhc3MgaXNcbiAqIGFkZGVkIHRvIHRoZSBtYWluIGVsZW1lbnQsIGFuZCBhIHRhcmdldCBlbGVtZW50IGlzIGluY2x1ZGVkLlxuICovXG5cbihmdW5jdGlvbiAoJCwgRHJ1cGFsKSB7XG5cbiAgZnVuY3Rpb24gaW5pdChpLCB0YWIpIHtcbiAgICB2YXIgJHRhYiA9ICQodGFiKTtcbiAgICB2YXIgJHRhcmdldCA9ICR0YWIuZmluZCgnW2RhdGEtZHJ1cGFsLW5hdi10YWJzLXRhcmdldF0nKTtcbiAgICB2YXIgaXNDb2xsYXBzaWJsZSA9ICR0YWIuaGFzQ2xhc3MoJ2lzLWNvbGxhcHNpYmxlJyk7XG5cbiAgICBmdW5jdGlvbiBvcGVuTWVudShlKSB7XG4gICAgICAkdGFyZ2V0LnRvZ2dsZUNsYXNzKCdpcy1vcGVuJyk7XG4gICAgfVxuXG4gICAgZnVuY3Rpb24gaGFuZGxlUmVzaXplKGUpIHtcbiAgICAgICR0YWIuYWRkQ2xhc3MoJ2lzLWhvcml6b250YWwnKTtcbiAgICAgIHZhciAkdGFicyA9ICR0YWIuZmluZCgnLnRhYnMnKTtcbiAgICAgIHZhciBpc0hvcml6b250YWwgPSAkdGFicy5vdXRlckhlaWdodCgpIDw9ICR0YWJzLmZpbmQoJy50YWJzX190YWInKS5vdXRlckhlaWdodCgpO1xuICAgICAgJHRhYi50b2dnbGVDbGFzcygnaXMtaG9yaXpvbnRhbCcsIGlzSG9yaXpvbnRhbCk7XG4gICAgICBpZiAoaXNDb2xsYXBzaWJsZSkge1xuICAgICAgICAkdGFiLnRvZ2dsZUNsYXNzKCdpcy1jb2xsYXBzZS1lbmFibGVkJywgIWlzSG9yaXpvbnRhbCk7XG4gICAgICB9XG4gICAgICBpZiAoaXNIb3Jpem9udGFsKSB7XG4gICAgICAgICR0YXJnZXQucmVtb3ZlQ2xhc3MoJ2lzLW9wZW4nKTtcbiAgICAgIH1cbiAgICB9XG5cbiAgICAkdGFiLmFkZENsYXNzKCdwb3NpdGlvbi1jb250YWluZXIgaXMtaG9yaXpvbnRhbC1lbmFibGVkJyk7XG5cbiAgICAkdGFiLm9uKCdjbGljay50YWJzJywgJ1tkYXRhLWRydXBhbC1uYXYtdGFicy10cmlnZ2VyXScsIG9wZW5NZW51KTtcbiAgICAkKHdpbmRvdykub24oJ3Jlc2l6ZS50YWJzJywgRHJ1cGFsLmRlYm91bmNlKGhhbmRsZVJlc2l6ZSwgMTUwKSkudHJpZ2dlcigncmVzaXplLnRhYnMnKTtcbiAgfVxuXG4gIC8qKlxuICAgKiBJbml0aWFsaXNlIHRoZSB0YWJzIEpTLlxuICAgKi9cbiAgRHJ1cGFsLmJlaGF2aW9ycy5uYXZUYWJzID0ge1xuICAgIGF0dGFjaDogZnVuY3Rpb24gKGNvbnRleHQsIHNldHRpbmdzKSB7XG4gICAgICB2YXIgJHRhYnMgPSAkKGNvbnRleHQpLmZpbmQoJ1tkYXRhLWRydXBhbC1uYXYtdGFic10nKTtcbiAgICAgIGlmICgkdGFicy5sZW5ndGgpIHtcbiAgICAgICAgdmFyIG5vdFNtYXJ0UGhvbmUgPSB3aW5kb3cubWF0Y2hNZWRpYSgnKG1pbi13aWR0aDogMzAwcHgpJyk7XG4gICAgICAgIGlmIChub3RTbWFydFBob25lLm1hdGNoZXMpIHtcbiAgICAgICAgICAkdGFicy5vbmNlKCduYXYtdGFicycpLmVhY2goaW5pdCk7XG4gICAgICAgIH1cbiAgICAgIH1cbiAgICB9XG4gIH07XG5cbn0pKGpRdWVyeSwgRHJ1cGFsKTtcbiJdLCJuYW1lcyI6W10sIm1hcHBpbmdzIjoiOzs7QUFBQTs7Ozs7Ozs7QUFRQSxDQUFDLFVBQVUsQ0FBQyxFQUFFLE1BQU0sRUFBRTs7RUFFcEIsU0FBUyxJQUFJLENBQUMsQ0FBQyxFQUFFLEdBQUcsRUFBRTtJQUNwQixJQUFJLElBQUksR0FBRyxDQUFDLENBQUMsR0FBRyxDQUFDLENBQUM7SUFDbEIsSUFBSSxPQUFPLEdBQUcsSUFBSSxDQUFDLElBQUksQ0FBQywrQkFBK0IsQ0FBQyxDQUFDO0lBQ3pELElBQUksYUFBYSxHQUFHLElBQUksQ0FBQyxRQUFRLENBQUMsZ0JBQWdCLENBQUMsQ0FBQzs7SUFFcEQsU0FBUyxRQUFRLENBQUMsQ0FBQyxFQUFFO01BQ25CLE9BQU8sQ0FBQyxXQUFXLENBQUMsU0FBUyxDQUFDLENBQUM7S0FDaEM7O0lBRUQsU0FBUyxZQUFZLENBQUMsQ0FBQyxFQUFFO01BQ3ZCLElBQUksQ0FBQyxRQUFRLENBQUMsZUFBZSxDQUFDLENBQUM7TUFDL0IsSUFBSSxLQUFLLEdBQUcsSUFBSSxDQUFDLElBQUksQ0FBQyxPQUFPLENBQUMsQ0FBQztNQUMvQixJQUFJLFlBQVksR0FBRyxLQUFLLENBQUMsV0FBVyxFQUFFLElBQUksS0FBSyxDQUFDLElBQUksQ0FBQyxZQUFZLENBQUMsQ0FBQyxXQUFXLEVBQUUsQ0FBQztNQUNqRixJQUFJLENBQUMsV0FBVyxDQUFDLGVBQWUsRUFBRSxZQUFZLENBQUMsQ0FBQztNQUNoRCxJQUFJLGFBQWEsRUFBRTtRQUNqQixJQUFJLENBQUMsV0FBVyxDQUFDLHFCQUFxQixFQUFFLENBQUMsWUFBWSxDQUFDLENBQUM7T0FDeEQ7TUFDRCxJQUFJLFlBQVksRUFBRTtRQUNoQixPQUFPLENBQUMsV0FBVyxDQUFDLFNBQVMsQ0FBQyxDQUFDO09BQ2hDO0tBQ0Y7O0lBRUQsSUFBSSxDQUFDLFFBQVEsQ0FBQywwQ0FBMEMsQ0FBQyxDQUFDOztJQUUxRCxJQUFJLENBQUMsRUFBRSxDQUFDLFlBQVksRUFBRSxnQ0FBZ0MsRUFBRSxRQUFRLENBQUMsQ0FBQztJQUNsRSxDQUFDLENBQUMsTUFBTSxDQUFDLENBQUMsRUFBRSxDQUFDLGFBQWEsRUFBRSxNQUFNLENBQUMsUUFBUSxDQUFDLFlBQVksRUFBRSxHQUFHLENBQUMsQ0FBQyxDQUFDLE9BQU8sQ0FBQyxhQUFhLENBQUMsQ0FBQztHQUN4Rjs7Ozs7RUFLRCxNQUFNLENBQUMsU0FBUyxDQUFDLE9BQU8sR0FBRztJQUN6QixNQUFNLEVBQUUsVUFBVSxPQUFPLEVBQUUsUUFBUSxFQUFFO01BQ25DLElBQUksS0FBSyxHQUFHLENBQUMsQ0FBQyxPQUFPLENBQUMsQ0FBQyxJQUFJLENBQUMsd0JBQXdCLENBQUMsQ0FBQztNQUN0RCxJQUFJLEtBQUssQ0FBQyxNQUFNLEVBQUU7UUFDaEIsSUFBSSxhQUFhLEdBQUcsTUFBTSxDQUFDLFVBQVUsQ0FBQyxvQkFBb0IsQ0FBQyxDQUFDO1FBQzVELElBQUksYUFBYSxDQUFDLE9BQU8sRUFBRTtVQUN6QixLQUFLLENBQUMsSUFBSSxDQUFDLFVBQVUsQ0FBQyxDQUFDLElBQUksQ0FBQyxJQUFJLENBQUMsQ0FBQztTQUNuQztPQUNGO0tBQ0Y7R0FDRixDQUFDOztDQUVILENBQUMsQ0FBQyxNQUFNLEVBQUUsTUFBTSxDQUFDLENBQUM7OyJ9