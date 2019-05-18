(function (exports) {
'use strict';

/**
 * @file
 * Smooth Scroll.
 */

(function ($) {

  /**
   * Speed of the smooth scroll.
   *
   * @type {Number}
   */
  var scrollSpeed = 300;

  /**
   * Additional offset in pixels.
   * DON'T worry about Drupal Admin toolbar, it is already calculated in. :)
   *
   *   negative: scroll past the item.
   *   0: stop exactly at the item.
   *   positive: scroll before the item.
   *
   * @type {Number}
   */
  var offset = 72;

  /**
   * Update the hash in the URL without jumping to the element.
   *
   * @param  {String} hash
   * @return {void}
   */
  var updateHash = function (hash) {
    if (history.pushState) { history.pushState(null, null, hash); }
    else { window.location.hash = hash; }
    // @fixme temp
    // $('.campaign-menu-link > a.active').removeClass('active')
    // $('a[href="' + hash + '"]').addClass('active')
  };

  /**
   * Applying the animation to all anchors, which have
   * <a href="#my-anchor"> format.
   */
  var smoothScroll = function (e) {
    e.preventDefault();
    updateHash(this.hash);

    if (location.pathname.replace(/^\//, '') === this.pathname.replace(/^\//, '') && location.hostname === this.hostname) {

      // Calculate admin toolbar height.
      // Both Toolbar and its Tray are 39px in default Drupal theme.
      var headerHeight = 0;
      var body = $('body');
      if (body.hasClass('toolbar-horizontal')) {
        headerHeight = 39;
        if (body.hasClass('toolbar-tray-open')) {
          headerHeight += 39;
        }
      }

      var target = $(this.hash);
      if (target.length) {
        $('html,body').animate({
          scrollTop: target.offset().top - headerHeight - offset
        }, scrollSpeed);
        var hamburger = $('#hamburger');
        if ($(window).width() < 768 && hamburger.hasClass('is-active')) {
          hamburger.removeClass('is-active');
        }
        return false
      }
    }
  };

  $('a[href*="#"]:not([href="#"]):not([href^="#tab-"]):not([href*="/#/"])').on('click', smoothScroll);

})(jQuery);

}((this.LaravelElixirBundle = this.LaravelElixirBundle || {})));
//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjpudWxsLCJzb3VyY2VzIjpbIkQ6L2RldmRlc2t0b3AvdGNzLmxvYy93ZWIvdGhlbWVzL3RpZXRvX2FkbWluL3NyYy9zY3JpcHRzL3Ntb290aC1zY3JvbGwuanMiXSwic291cmNlc0NvbnRlbnQiOlsiLyoqXG4gKiBAZmlsZVxuICogU21vb3RoIFNjcm9sbC5cbiAqL1xuXG4oZnVuY3Rpb24gKCQpIHtcblxuICAvKipcbiAgICogU3BlZWQgb2YgdGhlIHNtb290aCBzY3JvbGwuXG4gICAqXG4gICAqIEB0eXBlIHtOdW1iZXJ9XG4gICAqL1xuICBjb25zdCBzY3JvbGxTcGVlZCA9IDMwMFxuXG4gIC8qKlxuICAgKiBBZGRpdGlvbmFsIG9mZnNldCBpbiBwaXhlbHMuXG4gICAqIERPTidUIHdvcnJ5IGFib3V0IERydXBhbCBBZG1pbiB0b29sYmFyLCBpdCBpcyBhbHJlYWR5IGNhbGN1bGF0ZWQgaW4uIDopXG4gICAqXG4gICAqICAgbmVnYXRpdmU6IHNjcm9sbCBwYXN0IHRoZSBpdGVtLlxuICAgKiAgIDA6IHN0b3AgZXhhY3RseSBhdCB0aGUgaXRlbS5cbiAgICogICBwb3NpdGl2ZTogc2Nyb2xsIGJlZm9yZSB0aGUgaXRlbS5cbiAgICpcbiAgICogQHR5cGUge051bWJlcn1cbiAgICovXG4gIGNvbnN0IG9mZnNldCA9IDcyXG5cbiAgLyoqXG4gICAqIFVwZGF0ZSB0aGUgaGFzaCBpbiB0aGUgVVJMIHdpdGhvdXQganVtcGluZyB0byB0aGUgZWxlbWVudC5cbiAgICpcbiAgICogQHBhcmFtICB7U3RyaW5nfSBoYXNoXG4gICAqIEByZXR1cm4ge3ZvaWR9XG4gICAqL1xuICB2YXIgdXBkYXRlSGFzaCA9IChoYXNoKSA9PiB7XG4gICAgaWYgKGhpc3RvcnkucHVzaFN0YXRlKSBoaXN0b3J5LnB1c2hTdGF0ZShudWxsLCBudWxsLCBoYXNoKVxuICAgIGVsc2Ugd2luZG93LmxvY2F0aW9uLmhhc2ggPSBoYXNoXG4gICAgLy8gQGZpeG1lIHRlbXBcbiAgICAvLyAkKCcuY2FtcGFpZ24tbWVudS1saW5rID4gYS5hY3RpdmUnKS5yZW1vdmVDbGFzcygnYWN0aXZlJylcbiAgICAvLyAkKCdhW2hyZWY9XCInICsgaGFzaCArICdcIl0nKS5hZGRDbGFzcygnYWN0aXZlJylcbiAgfVxuXG4gIC8qKlxuICAgKiBBcHBseWluZyB0aGUgYW5pbWF0aW9uIHRvIGFsbCBhbmNob3JzLCB3aGljaCBoYXZlXG4gICAqIDxhIGhyZWY9XCIjbXktYW5jaG9yXCI+IGZvcm1hdC5cbiAgICovXG4gIHZhciBzbW9vdGhTY3JvbGwgPSBmdW5jdGlvbiAoZSkge1xuICAgIGUucHJldmVudERlZmF1bHQoKVxuICAgIHVwZGF0ZUhhc2godGhpcy5oYXNoKVxuXG4gICAgaWYgKGxvY2F0aW9uLnBhdGhuYW1lLnJlcGxhY2UoL15cXC8vLCAnJykgPT0gdGhpcy5wYXRobmFtZS5yZXBsYWNlKC9eXFwvLywgJycpICYmIGxvY2F0aW9uLmhvc3RuYW1lID09IHRoaXMuaG9zdG5hbWUpIHtcblxuICAgICAgLy8gQ2FsY3VsYXRlIGFkbWluIHRvb2xiYXIgaGVpZ2h0LlxuICAgICAgLy8gQm90aCBUb29sYmFyIGFuZCBpdHMgVHJheSBhcmUgMzlweCBpbiBkZWZhdWx0IERydXBhbCB0aGVtZS5cbiAgICAgIHZhciBoZWFkZXJIZWlnaHQgPSAwXG4gICAgICBpZiAoJCgnYm9keScpLmhhc0NsYXNzKCd0b29sYmFyLWhvcml6b250YWwnKSkge1xuICAgICAgICBoZWFkZXJIZWlnaHQgPSAzOVxuICAgICAgICBpZiAoJCgnYm9keScpLmhhc0NsYXNzKCd0b29sYmFyLXRyYXktb3BlbicpKSB7XG4gICAgICAgICAgaGVhZGVySGVpZ2h0ICs9IDM5XG4gICAgICAgIH1cbiAgICAgIH1cblxuICAgICAgdmFyIHRhcmdldCA9ICQodGhpcy5oYXNoKVxuICAgICAgaWYgKHRhcmdldC5sZW5ndGgpIHtcbiAgICAgICAgJCgnaHRtbCxib2R5JykuYW5pbWF0ZSh7XG4gICAgICAgICAgc2Nyb2xsVG9wOiB0YXJnZXQub2Zmc2V0KCkudG9wIC0gaGVhZGVySGVpZ2h0IC0gb2Zmc2V0XG4gICAgICAgIH0sIHNjcm9sbFNwZWVkKVxuICAgICAgICBpZiAoJCh3aW5kb3cpLndpZHRoKCkgPCA3NjggJiYgJCgnI2hhbWJ1cmdlcicpLmhhc0NsYXNzKCdpcy1hY3RpdmUnKSkge1xuICAgICAgICAgICQoJyNoYW1idXJnZXInKS5yZW1vdmVDbGFzcygnaXMtYWN0aXZlJylcbiAgICAgICAgfVxuICAgICAgICByZXR1cm4gZmFsc2VcbiAgICAgIH1cbiAgICB9XG4gIH1cblxuICAkKCdhW2hyZWYqPVwiI1wiXTpub3QoW2hyZWY9XCIjXCJdKTpub3QoW2hyZWZePVwiI3RhYi1cIl0pJykub24oJ2NsaWNrJywgc21vb3RoU2Nyb2xsKVxuXG59KShqUXVlcnkpXG4iXSwibmFtZXMiOlsiY29uc3QiXSwibWFwcGluZ3MiOiI7OztBQUFBOzs7OztBQUtBLENBQUMsVUFBVSxDQUFDLEVBQUU7Ozs7Ozs7RUFPWkEsSUFBTSxXQUFXLEdBQUcsR0FBRyxDQUFBOzs7Ozs7Ozs7Ozs7RUFZdkJBLElBQU0sTUFBTSxHQUFHLEVBQUUsQ0FBQTs7Ozs7Ozs7RUFRakIsSUFBSSxVQUFVLEdBQUcsVUFBQyxJQUFJLEVBQUU7SUFDdEIsSUFBSSxPQUFPLENBQUMsU0FBUyxFQUFFLEVBQUEsT0FBTyxDQUFDLFNBQVMsQ0FBQyxJQUFJLEVBQUUsSUFBSSxFQUFFLElBQUksQ0FBQyxDQUFBLEVBQUE7U0FDckQsRUFBQSxNQUFNLENBQUMsUUFBUSxDQUFDLElBQUksR0FBRyxJQUFJLENBQUEsRUFBQTs7OztHQUlqQyxDQUFBOzs7Ozs7RUFNRCxJQUFJLFlBQVksR0FBRyxVQUFVLENBQUMsRUFBRTtJQUM5QixDQUFDLENBQUMsY0FBYyxFQUFFLENBQUE7SUFDbEIsVUFBVSxDQUFDLElBQUksQ0FBQyxJQUFJLENBQUMsQ0FBQTs7SUFFckIsSUFBSSxRQUFRLENBQUMsUUFBUSxDQUFDLE9BQU8sQ0FBQyxLQUFLLEVBQUUsRUFBRSxDQUFDLElBQUksSUFBSSxDQUFDLFFBQVEsQ0FBQyxPQUFPLENBQUMsS0FBSyxFQUFFLEVBQUUsQ0FBQyxJQUFJLFFBQVEsQ0FBQyxRQUFRLElBQUksSUFBSSxDQUFDLFFBQVEsRUFBRTs7OztNQUlsSCxJQUFJLFlBQVksR0FBRyxDQUFDLENBQUE7TUFDcEIsSUFBSSxDQUFDLENBQUMsTUFBTSxDQUFDLENBQUMsUUFBUSxDQUFDLG9CQUFvQixDQUFDLEVBQUU7UUFDNUMsWUFBWSxHQUFHLEVBQUUsQ0FBQTtRQUNqQixJQUFJLENBQUMsQ0FBQyxNQUFNLENBQUMsQ0FBQyxRQUFRLENBQUMsbUJBQW1CLENBQUMsRUFBRTtVQUMzQyxZQUFZLElBQUksRUFBRSxDQUFBO1NBQ25CO09BQ0Y7O01BRUQsSUFBSSxNQUFNLEdBQUcsQ0FBQyxDQUFDLElBQUksQ0FBQyxJQUFJLENBQUMsQ0FBQTtNQUN6QixJQUFJLE1BQU0sQ0FBQyxNQUFNLEVBQUU7UUFDakIsQ0FBQyxDQUFDLFdBQVcsQ0FBQyxDQUFDLE9BQU8sQ0FBQztVQUNyQixTQUFTLEVBQUUsTUFBTSxDQUFDLE1BQU0sRUFBRSxDQUFDLEdBQUcsR0FBRyxZQUFZLEdBQUcsTUFBTTtTQUN2RCxFQUFFLFdBQVcsQ0FBQyxDQUFBO1FBQ2YsSUFBSSxDQUFDLENBQUMsTUFBTSxDQUFDLENBQUMsS0FBSyxFQUFFLEdBQUcsR0FBRyxJQUFJLENBQUMsQ0FBQyxZQUFZLENBQUMsQ0FBQyxRQUFRLENBQUMsV0FBVyxDQUFDLEVBQUU7VUFDcEUsQ0FBQyxDQUFDLFlBQVksQ0FBQyxDQUFDLFdBQVcsQ0FBQyxXQUFXLENBQUMsQ0FBQTtTQUN6QztRQUNELE9BQU8sS0FBSztPQUNiO0tBQ0Y7R0FDRixDQUFBOztFQUVELENBQUMsQ0FBQyxtREFBbUQsQ0FBQyxDQUFDLEVBQUUsQ0FBQyxPQUFPLEVBQUUsWUFBWSxDQUFDLENBQUE7O0NBRWpGLENBQUMsQ0FBQyxNQUFNLENBQUMsQ0FBQTs7In0=