'use strict';

var _extends = Object.assign || function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; };

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

Drupal.Toolshed = Drupal.Toolshed || {};

(function ($, Toolshed) {
  Toolshed.Tooltip = function () {
    function _class(element, trigger) {
      var settings = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : {};

      _classCallCheck(this, _class);

      this.settings = _extends({
        event: 'click', // What action triggers the tooltip to be visible.
        position: 'absolute', // Position the tooltip relative or absolute.
        exclusive: true, // Only one exclusive tooltip open at a time.
        closeOnClickOff: true, // Close tip if user clicks else where.
        attachTo: element }, settings);

      this.trigger = trigger;
      if (!(this.trigger && this.trigger.length)) {
        this.trigger = $('<a class="toolshed-tip__trigger" title="Click for help text." href="#">?</a>').insertAfter(this.attachTo);
      } else if (!this.trigger.hasClass('toolshed-tip__trigger')) {
        this.trigger.addClass('toolshed-top__trigger');
      }

      this.element = element.wrap('<div class="toolshed-tip__content">').parent().hide();
      this.trigger.on(this.event, this, this.onToggle);

      // Move the scope of the tooltip help to the general document.
      if (this.settings.position === 'absolute') {
        $('body').append(this.element);
      }
    }

    _createClass(_class, [{
      key: 'adjustPosition',
      value: function adjustPosition() {
        if (!this.offsetTop || !this.offsetLeft) {
          this.offsetTop = this.trigger.outerHeight() / 2 - 16;
          this.offsetLeft = this.trigger.outerWidth() + 32;
        }

        var pos = this.settings.position === 'absolute' ? this.trigger.offset() : this.trigger.position();
        pos.top += this.offsetTop;
        pos.left += this.offsetLeft;
        this.element.css(pos);
      }
    }, {
      key: 'show',
      value: function show() {
        if (this.exclusive) {
          if (Drupal.Toolshed.activeTooltip) {
            Drupal.Toolshed.activeTooltip.close();
          }

          // Mark this tooltip as the active tooltip.
          Drupal.Toolshed.activeTooltip = this;
        }

        this.trigger.addClass('tip--active');
        this.adjustPosition();
        this.element.show();

        // Close if user clicks somewhere else.
        $('body').on('click', this, this.onClose);
      }
    }, {
      key: 'close',
      value: function close() {
        if (this.element.is(':visible')) {
          this.element.hide();
          this.trigger.removeClass('tip--active');

          // If marked as active, remove it from the closed link.
          if (this === Drupal.Toolshed.activeTooltip) {
            Drupal.Toolshed.activeTooltip = null;
          }
        }
      }

      // --------------------
      // Handle events

    }], [{
      key: 'onToggle',
      value: function onToggle(event) {
        var tooltip = event.data;
        event.preventDefault();
        event.stopImmediatePropagation();

        tooltip[tooltip.element.is(':visible') ? 'close' : 'show']();
      }
    }, {
      key: 'onClose',
      value: function onClose(event) {
        var tooltip = event.data;

        tooltip.close();
        $('body').off('click', tooltip.close);
      }
    }]);

    return _class;
  }();
})(jQuery, Drupal.Toolshed);
//# sourceMappingURL=data:application/json;charset=utf8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbIndpZGdldHMvdG9vbHRpcC5lczYuanMiXSwibmFtZXMiOlsiRHJ1cGFsIiwiVG9vbHNoZWQiLCIkIiwiVG9vbHRpcCIsImVsZW1lbnQiLCJ0cmlnZ2VyIiwic2V0dGluZ3MiLCJldmVudCIsInBvc2l0aW9uIiwiZXhjbHVzaXZlIiwiY2xvc2VPbkNsaWNrT2ZmIiwiYXR0YWNoVG8iLCJsZW5ndGgiLCJpbnNlcnRBZnRlciIsImhhc0NsYXNzIiwiYWRkQ2xhc3MiLCJ3cmFwIiwicGFyZW50IiwiaGlkZSIsIm9uIiwib25Ub2dnbGUiLCJhcHBlbmQiLCJvZmZzZXRUb3AiLCJvZmZzZXRMZWZ0Iiwib3V0ZXJIZWlnaHQiLCJvdXRlcldpZHRoIiwicG9zIiwib2Zmc2V0IiwidG9wIiwibGVmdCIsImNzcyIsImFjdGl2ZVRvb2x0aXAiLCJjbG9zZSIsImFkanVzdFBvc2l0aW9uIiwic2hvdyIsIm9uQ2xvc2UiLCJpcyIsInJlbW92ZUNsYXNzIiwidG9vbHRpcCIsImRhdGEiLCJwcmV2ZW50RGVmYXVsdCIsInN0b3BJbW1lZGlhdGVQcm9wYWdhdGlvbiIsIm9mZiIsImpRdWVyeSJdLCJtYXBwaW5ncyI6Ijs7Ozs7Ozs7QUFDQUEsT0FBT0MsUUFBUCxHQUFrQkQsT0FBT0MsUUFBUCxJQUFtQixFQUFyQzs7QUFFQSxDQUFDLFVBQUNDLENBQUQsRUFBSUQsUUFBSixFQUFpQjtBQUNoQkEsV0FBU0UsT0FBVDtBQUNFLG9CQUFZQyxPQUFaLEVBQXFCQyxPQUFyQixFQUE2QztBQUFBLFVBQWZDLFFBQWUsdUVBQUosRUFBSTs7QUFBQTs7QUFDM0MsV0FBS0EsUUFBTDtBQUNFQyxlQUFPLE9BRFQsRUFDa0I7QUFDaEJDLGtCQUFVLFVBRlosRUFFd0I7QUFDdEJDLG1CQUFXLElBSGIsRUFHbUI7QUFDakJDLHlCQUFpQixJQUpuQixFQUl5QjtBQUN2QkMsa0JBQVVQLE9BTFosSUFNS0UsUUFOTDs7QUFTQSxXQUFLRCxPQUFMLEdBQWVBLE9BQWY7QUFDQSxVQUFJLEVBQUUsS0FB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QUFBLDhCQWlGaUI3QixLQWpGakIsRUFpRndCO0FBQ3BCLFlBQU0rQixVQUFVL0IsTUFBTWdDLElBQXRCOztBQUVBRCxnQkFBUU4sS0FBUjtBQUNBOUIsVUFBRSxNQUFGLEVBQVV3QyxHQUFWLENBQWMsT0FBZCxFQUF1QkosUUFBUU4sS0FBL0I7QUFDRDtBQXRGSDs7QUFBQTtBQUFBO0FBd0ZELENBekZELEVBeUZHVyxNQXpGSCxFQXlGVzNDLE9BQU9DLFFBekZsQiIsImZpbGUiOiJ3aWRnZXRzL3Rvb2x0aXAuanMiLCJzb3VyY2VzQ29udGVudCI6WyJcbkRydXBhbC5Ub29sc2hlZCA9IERydXBhbC5Ub29sc2hlZCB8fCB7fTtcblxuKCgkLCBUb29sc2hlZCkgPT4ge1xuICBUb29sc2hlZC5Ub29sdGlwID0gY2xhc3Mge1xuICAgIGNvbnN0cnVjdG9yKGVsZW1lbnQsIHRyaWdnZXIsIHNldHRpbmdzID0ge30pIHtcbiAgICAgIHRoaXMuc2V0dGluZ3MgPSB7XG4gICAgICAgIGV2ZW50OiAnY2xpY2snLCAvLyBXaGF0IGFjdGlvbiB0cmlnZ2VycyB0aGUgdG9vbHRpcCB0byBiZSB2aXNpYmxlLlxuICAgICAgICBwb3NpdGlvbjogJ2Fic29sdXRlJywgLy8gUG9zaXRpb24gdGhlIHRvb2x0aXAgcmVsYXRpdmUgb3IgYWJzb2x1dGUuXG4gICAgICAgIGV4Y2x1c2l2ZTogdHJ1ZSwgLy8gT25seSBvbmUgZXhjbHVzaXZlIHRvb2x0aXAgb3BlbiBhdCBhIHRpbWUuXG4gICAgICAgIGNsb3NlT25DbGlja09mZjogdHJ1ZSwgLy8gQ2xvc2UgdGlwIGlmIHVzZXIgY2xpY2tzIGVsc2Ugd2hlcmUuXG4gICAgICAgIGF0dGFjaFRvOiBlbGVtZW50LCAvLyBXaGVyZSBkbyB3ZSBwdXQgdGhlIHRvb2x0aXAgdHJpZ2dlcj9cbiAgICAgICAgLi4uc2V0dGluZ3MsXG4gICAgICB9O1xuXG4gICAgICB0aGlzLnRyaWdnZXIgPSB0cmlnZ2VyO1xuICAgICAgaWYgKCEodGhpcy50cmlnZ2VyICYmIHRoaXMudHJpZ2dlci5sZW5ndGgpKSB7XG4gICAgICAgIHRoaXMudHJpZ2dlciA9ICQoJzxhIGNsYXNzPVwidG9vbHNoZWQtdGlwX190cmlnZ2VyXCIgdGl0bGU9XCJDbGljayBmb3IgaGVscCB0ZXh0LlwiIGhyZWY9XCIjXCI+PzwvYT4nKVxuICAgICAgICAgIC5pbnNlcnRBZnRlcih0aGlzLmF0dGFjaFRvKTtcbiAgICAgIH1cbiAgICAgIGVsc2UgaWYgKCF0aGlzLnRyaWdnZXIuaGFzQ2xhc3MoJ3Rvb2xzaGVkLXRpcF9fdHJpZ2dlcicpKSB7XG4gICAgICAgIHRoaXMudHJpZ2dlci5hZGRDbGFzcygndG9vbHNoZWQtdG9wX190cmlnZ2VyJyk7XG4gICAgICB9XG5cbiAgICAgIHRoaXMuZWxlbWVudCA9IGVsZW1lbnQud3JhcCgnPGRpdiBjbGFzcz1cInRvb2xzaGVkLXRpcF9fY29udGVudFwiPicpLnBhcmVudCgpLmhpZGUoKTtcbiAgICAgIHRoaXMudHJpZ2dlci5vbih0aGlzLmV2ZW50LCB0aGlzLCB0aGlzLm9uVG9nZ2xlKTtcblxuICAgICAgLy8gTW92ZSB0aGUgc2NvcGUgb2YgdGhlIHRvb2x0aXAgaGVscCB0byB0aGUgZ2VuZXJhbCBkb2N1bWVudC5cbiAgICAgIGlmICh0aGlzLnNldHRpbmdzLnBvc2l0aW9uID09PSAnYWJzb2x1dGUnKSB7XG4gICAgICAgICQoJ2JvZHknKS5hcHBlbmQodGhpcy5lbGVtZW50KTtcbiAgICAgIH1cbiAgICB9XG5cbiAgICBhZGp1c3RQb3NpdGlvbigpIHtcbiAgICAgIGlmICghdGhpcy5vZmZzZXRUb3AgfHwgIXRoaXMub2Zmc2V0TGVmdCkge1xuICAgICAgICB0aGlzLm9mZnNldFRvcCA9ICh0aGlzLnRyaWdnZXIub3V0ZXJIZWlnaHQoKSAvIDIpIC0gMTY7XG4gICAgICAgIHRoaXMub2Zmc2V0TGVmdCA9IHRoaXMudHJpZ2dlci5vdXRlcldpZHRoKCkgKyAzMjtcbiAgICAgIH1cblxuICAgICAgY29uc3QgcG9zID0gdGhpcy5zZXR0aW5ncy5wb3NpdGlvbiA9PT0gJ2Fic29sdXRlJyA/IHRoaXMudHJpZ2dlci5vZmZzZXQoKSA6IHRoaXMudHJpZ2dlci5wb3NpdGlvbigpO1xuICAgICAgcG9zLnRvcCArPSB0aGlzLm9mZnNldFRvcDtcbiAgICAgIHBvcy5sZWZ0ICs9IHRoaXMub2Zmc2V0TGVmdDtcbiAgICAgIHRoaXMuZWxlbWVudC5jc3MocG9zKTtcbiAgICB9XG5cbiAgICBzaG93KCkge1xuICAgICAgaWYgKHRoaXMuZXhjbHVzaXZlKSB7XG4gICAgICAgIGlmIChEcnVwYWwuVG9vbHNoZWQuYWN0aXZlVG9vbHRpcCkge1xuICAgICAgICAgIERydXBhbC5Ub29sc2hlZC5hY3RpdmVUb29sdGlwLmNsb3NlKCk7XG4gICAgICAgIH1cblxuICAgICAgICAvLyBNYXJrIHRoaXMgdG9vbHRpcCBhcyB0aGUgYWN0aXZlIHRvb2x0aXAuXG4gICAgICAgIERydXBhbC5Ub29sc2hlZC5hY3RpdmVUb29sdGlwID0gdGhpcztcbiAgICAgIH1cblxuICAgICAgdGhpcy50cmlnZ2VyLmFkZENsYXNzKCd0aXAtLWFjdGl2ZScpO1xuICAgICAgdGhpcy5hZGp1c3RQb3NpdGlvbigpO1xuICAgICAgdGhpcy5lbGVtZW50LnNob3coKTtcblxuICAgICAgLy8gQ2xvc2UgaWYgdXNlciBjbGlja3Mgc29tZXdoZXJlIGVsc2UuXG4gICAgICAkKCdib2R5Jykub24oJ2NsaWNrJywgdGhpcywgdGhpcy5vbkNsb3NlKTtcbiAgICB9XG5cbiAgICBjbG9zZSgpIHtcbiAgICAgIGlmICh0aGlzLmVsZW1lbnQuaXMoJzp2aXNpYmxlJykpIHtcbiAgICAgICAgdGhpcy5lbGVtZW50LmhpZGUoKTtcbiAgICAgICAgdGhpcy50cmlnZ2VyLnJlbW92ZUNsYXNzKCd0aXAtLWFjdGl2ZScpO1xuXG4gICAgICAgIC8vIElmIG1hcmtlZCBhcyBhY3RpdmUsIHJlbW92ZSBpdCBmcm9tIHRoZSBjbG9zZWQgbGluay5cbiAgICAgICAgaWYgKHRoaXMgPT09IERydXBhbC5Ub29sc2hlZC5hY3RpdmVUb29sdGlwKSB7XG4gICAgICAgICAgRHJ1cGFsLlRvb2xzaGVkLmFjdGl2ZVRvb2x0aXAgPSBudWxsO1xuICAgICAgICB9XG4gICAgICB9XG4gICAgfVxuXG4gICAgLy8gLS0tLS0tLS0tLS0tLS0tLS0tLS1cbiAgICAvLyBIYW5kbGUgZXZlbnRzXG4gICAgc3RhdGljIG9uVG9nZ2xlKGV2ZW50KSB7XG4gICAgICBjb25zdCB0b29sdGlwID0gZXZlbnQuZGF0YTtcbiAgICAgIGV2ZW50LnByZXZlbnREZWZhdWx0KCk7XG4gICAgICBldmVudC5zdG9wSW1tZWRpYXRlUHJvcGFnYXRpb24oKTtcblxuICAgICAgdG9vbHRpcFt0b29sdGlwLmVsZW1lbnQuaXMoJzp2aXNpYmxlJykgPyAnY2xvc2UnIDogJ3Nob3cnXSgpO1xuICAgIH1cblxuICAgIHN0YXRpYyBvbkNsb3NlKGV2ZW50KSB7XG4gICAgICBjb25zdCB0b29sdGlwID0gZXZlbnQuZGF0YTtcblxuICAgICAgdG9vbHRpcC5jbG9zZSgpO1xuICAgICAgJCgnYm9keScpLm9mZignY2xpY2snLCB0b29sdGlwLmNsb3NlKTtcbiAgICB9XG4gIH07XG59KShqUXVlcnksIERydXBhbC5Ub29sc2hlZCk7XG4iXX0=
