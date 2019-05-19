"use strict";

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

/* eslint no-bitwise: ["error", { "allow": ["|"] }] */
(function (_ref) {
  var Toolshed = _ref.Toolshed;

  Toolshed.Geom = {};

  /**
   * Defines a enumerations for different edges.
   *
   * @type {Object}
   */
  Toolshed.Geom.Edges = function () {
    function _class() {
      _classCallCheck(this, _class);
    }

    _createClass(_class, null, [{
      key: "TOP",
      get: function get() {
        return 0x01;
      }
    }, {
      key: "BOTTOM",
      get: function get() {
        return 0x02;
      }
    }, {
      key: "LEFT",
      get: function get() {
        return 0x10;
      }
    }, {
      key: "RIGHT",
      get: function get() {
        return 0x20;
      }
    }]);

    return _class;
  }();

  /**
   * Defines a directions enumerations or mask.
   *
   * @type {Object}
   */
  Toolshed.Geom.Direction = function () {
    function _class2() {
      _classCallCheck(this, _class2);
    }

    _createClass(_class2, null, [{
      key: "VERTICAL",
      get: function get() {
        return Toolshed.Geom.Edges.TOP | Toolshed.Geom.Edges.BOTTOM;
      }
    }, {
      key: "HORIZONTAL",
      get: function get() {
        return Toolshed.Geom.Edges.LEFT | Toolshed.Geom.Edges.RIGHT;
      }
    }, {
      key: "ANY",
      get: function get() {
        return Toolshed.Geom.Direction.HORIZONTAL | Toolshed.Geom.Direction.VERTICAL;
      }
    }]);

    return _class2;
  }();

  /**
   * Coordinates to decribe a rectangular region in space.
   */
  Toolshed.Geom.Rect = function () {
    /**
     * @param {Toolshed.Rect|int} left
     *  Either a jQuery wrapper or Rect object to build the rectangle with.
     *  If just an integer, use this value as the left edge of the rectangle.
     * @param {int} top
     *  The coordinate of the top edge of the rectangle.
     * @param {int} right
     *  The coordinate of the right edge of the rectangle.
     * @param {int} bottom
     *  The coordinate of the bottom edge of the rectangle.
     */
    function _class3(left, top, right, bottom) {
      _classCallCheck(this, _class3);

      if (left instanceof Toolshed.Geom.Rect) {
        Object.assign(this, left);
      } else {
        if (top <= bottom) {
          this.top = top;
          this.bottom = bottom;
        } else {
          this.top = bottom;
          this.bottom = top;
        }

        if (left <= right) {
          this.left = left;
          this.right = right;
        } else {
          this.left = right;
          this.right = left;
        }
      }
    }

    _createClass(_class3, [{
      key: "getPosition",
      value: function getPosition() {
        return { left: this.left, top: this.top };
      }
    }, {
      key: "getWidth",
      value: function getWidth() {
        return this.right - this.left;
      }
    }, {
      key: "getHeight",
      value: function getHeight() {
        return this.bottom - this.top;
      }
    }, {
      key: "isInRect",
      value: function isInRect(pt) {
        var pos = this.getPosition();
        pt.left -= pos.left;
        pt.top -= pos.top;

        return pt.left >= 0 && pt.left <= this.getWidth() && pt.top >= 0 && pt.top <= this.getHeight();
      }
    }, {
      key: "getArea",
      value: function getArea() {
        return this.getWidth() * this.getHeight();
      }
    }, {
      key: "offset",
      value: function offset(xOffset, yOffset) {
        this.top += yOffset;
        this.bottom += yOffset;
        this.left += xOffset;
        this.right += xOffset;
      }
    }, {
      key: "getIntersection",
      value: function getIntersection(rect) {
        var o1 = rect.getPosition();
        var o2 = this.getPosition();

        var x = Math.max(o1.left, o2.left);
        var y = Math.max(o1.top, o2.top);
        var r = Math.min(o1.left + rect.getWidth(), o2.left + this.getWidth());
        var b = Math.min(o1.top + rect.getHeight(), o2.top + this.getHeight());

        // Check that this point is in the rectangle.
        return x > r || y > b ? null : new Toolshed.Geom.Rect(x, y, r, b);
      }
    }, {
      key: "contains",
      value: function contains(rect) {
        var a = rect.getPosition();
        var b = this.getPosition();

        return a.left >= b.left && a.left + rect.getWidth() <= b.left + this.getWidth() && a.top >= b.top && a.top + rect.getHeight() <= b.top + this.getHeight();
      }
    }]);

    return _class3;
  }();
})(Drupal);
//# sourceMappingURL=data:application/json;charset=utf8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbImdlb20uZXM2LmpzIl0sIm5hbWVzIjpbIlRvb2xzaGVkIiwiR2VvbSIsIkVkZ2VzIiwiRGlyZWN0aW9uIiwiVE9QIiwiQk9UVE9NIiwiTEVGVCIsIlJJR0hUIiwiSE9SSVpPTlRBTCIsIlZFUlRJQ0FMIiwiUmVjdCIsImxlZnQiLCJ0b3AiLCJyaWdodCIsImJvdHRvbSIsIk9iamVjdCIsImFzc2lnbiIsInB0IiwicG9zIiwiZ2V0UG9zaXRpb24iLCJnZXRXaWR0aCIsImdldEhlaWdodCIsInhPZmZzZXQiLCJ5T2Zmc2V0IiwicmVjdCIsIm8xIiwibzIiLCJ4IiwiTWF0aCIsIm1heCIsInkiLCJyIiwibWluIiwiYiIsImEiLCJEcnVwYWwiXSwibWFwcGluZ3MiOiI7Ozs7OztBQUFBO0FBQ0EsQ0FBQyxnQkFBa0I7QUFBQSxNQUFmQSxRQUFlLFFBQWZBLFFBQWU7O0FBQ2pCQSxXQUFTQyxJQUFULEdBQWdCLEVBQWhCOztBQUVBOzs7OztBQUtBRCxXQUFTQyxJQUFULENBQWNDLEtBQWQ7QUFBQTtBQUFBO0FBQUE7O0FBQUE7QUFBQTtBQUFBLDBCQUNtQjtBQUNmLGVBQU8sSUFBUDtBQUNEO0FBSEg7QUFBQTtBQUFBLDBCQUtzQjtBQUNsQixlQUFPLElBQVA7QUFDRDtBQVBIO0FBQUE7QUFBQSwwQkFTb0I7QUFDaEIsZUFBTyxJQUFQO0FBQ0Q7QUFYSDtBQUFBO0FBQUEsMEJBYXFCO0FBQ2pCLGVBQU8sSUFBUDtBQUNEO0FBZkg7O0FBQUE7QUFBQTs7QUFrQkE7Ozs7O0FBS0FGLFdBQVNDLElBQVQsQ0FBY0UsU0FBZDtBQUFBO0FBQUE7QUFBQTs7QUFBQTtBQUFBO0FBQUEsMEJBQ3dCO0FBQ3BCLGVBQU9ILFNBQVNDLElBQVQsQ0FBY0MsS0FBZCxDQUFvQkUsR0FBcEIsR0FBMEJKLFNBQVNDLElBQVQsQ0FBY0MsS0FBZCxDQUFvQkcsTUFBckQ7QUFDRDtBQUhIO0FBQUE7QUFBQSwwQkFLMEI7QUFDdEIsZUFBT0wsU0FBU0MsSUFBVCxDQUFjQyxLQUFkLENBQW9C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R2YsSUFBSCxHQUFVLEtBQUtTLFFBQUwsRUFBOUMsQ0FBVjtBQUNBLFlBQU1hLElBQUlMLEtBQUtJLEdBQUwsQ0FBU1AsR0FBR2IsR0FBSCxHQUFTWSxLQUFLSCxTQUFMLEVBQWxCLEVBQW9DSyxHQUFHZCxHQUFILEdBQVMsS0FBS1MsU0FBTCxFQUE3QyxDQUFWOztBQUVBO0FBQ0EsZUFBUU0sSUFBSUksQ0FBSixJQUFTRCxJQUFJRyxDQUFkLEdBQW1CLElBQW5CLEdBQTBCLElBQUlqQyxTQUFTQyxJQUFULENBQWNTLElBQWxCLENBQXVCaUIsQ0FBdkIsRUFBMEJHLENBQTFCLEVBQTZCQyxDQUE3QixFQUFnQ0UsQ0FBaEMsQ0FBakM7QUFDRDtBQWhGSDtBQUFBO0FBQUEsK0JBa0ZXVCxJQWxGWCxFQWtGaUI7QUFDYixZQUFNVSxJQUFJVixLQUFLTCxXQUFMLEVBQVY7QUFDQSxZQUFNYyxJQUFJLEtBQUtkLFdBQUwsRUFBVjs7QUFFQSxlQUFPZSxFQUFFdkIsSUFBRixJQUFVc0IsRUFBRXRCLElBQVosSUFBb0J1QixFQUFFdkIsSUFBRixHQUFTYSxLQUFLSixRQUFMLEVBQVQsSUFBNEJhLEVBQUV0QixJQUFGLEdBQVMsS0FBS1MsUUFBTCxFQUF6RCxJQUNGYyxFQUFFdEIsR0FBRixJQUFTcUIsRUFBRXJCLEdBRFQsSUFDZ0JzQixFQUFFdEIsR0FBRixHQUFRWSxLQUFLSCxTQUFMLEVBQVIsSUFBNEJZLEVBQUVyQixHQUFGLEdBQVEsS0FBS1MsU0FBTCxFQUQzRDtBQUVEO0FBeEZIOztBQUFBO0FBQUE7QUEwRkQsQ0ExSUQsRUEwSUdjLE1BMUlIIiwiZmlsZSI6Imdlb20uanMiLCJzb3VyY2VzQ29udGVudCI6WyIvKiBlc2xpbnQgbm8tYml0d2lzZTogW1wiZXJyb3JcIiwgeyBcImFsbG93XCI6IFtcInxcIl0gfV0gKi9cbigoeyBUb29sc2hlZCB9KSA9PiB7XG4gIFRvb2xzaGVkLkdlb20gPSB7fTtcblxuICAvKipcbiAgICogRGVmaW5lcyBhIGVudW1lcmF0aW9ucyBmb3IgZGlmZmVyZW50IGVkZ2VzLlxuICAgKlxuICAgKiBAdHlwZSB7T2JqZWN0fVxuICAgKi9cbiAgVG9vbHNoZWQuR2VvbS5FZGdlcyA9IGNsYXNzIHtcbiAgICBzdGF0aWMgZ2V0IFRPUCgpIHtcbiAgICAgIHJldHVybiAweDAxO1xuICAgIH1cblxuICAgIHN0YXRpYyBnZXQgQk9UVE9NKCkge1xuICAgICAgcmV0dXJuIDB4MDI7XG4gICAgfVxuXG4gICAgc3RhdGljIGdldCBMRUZUKCkge1xuICAgICAgcmV0dXJuIDB4MTA7XG4gICAgfVxuXG4gICAgc3RhdGljIGdldCBSSUdIVCgpIHtcbiAgICAgIHJldHVybiAweDIwO1xuICAgIH1cbiAgfTtcblxuICAvKipcbiAgICogRGVmaW5lcyBhIGRpcmVjdGlvbnMgZW51bWVyYXRpb25zIG9yIG1hc2suXG4gICAqXG4gICAqIEB0eXBlIHtPYmplY3R9XG4gICAqL1xuICBUb29sc2hlZC5HZW9tLkRpcmVjdGlvbiA9IGNsYXNzIHtcbiAgICBzdGF0aWMgZ2V0IFZFUlRJQ0FMKCkge1xuICAgICAgcmV0dXJuIFRvb2xzaGVkLkdlb20uRWRnZXMuVE9QIHwgVG9vbHNoZWQuR2VvbS5FZGdlcy5CT1RUT007XG4gICAgfVxuXG4gICAgc3RhdGljIGdldCBIT1JJWk9OVEFMKCkge1xuICAgICAgcmV0dXJuIFRvb2xzaGVkLkdlb20uRWRnZXMuTEVGVCB8IFRvb2xzaGVkLkdlb20uRWRnZXMuUklHSFQ7XG4gICAgfVxuXG4gICAgc3RhdGljIGdldCBBTlkoKSB7XG4gICAgICByZXR1cm4gVG9vbHNoZWQuR2VvbS5EaXJlY3Rpb24uSE9SSVpPTlRBTCB8IFRvb2xzaGVkLkdlb20uRGlyZWN0aW9uLlZFUlRJQ0FMO1xuICAgIH1cbiAgfTtcblxuICAvKipcbiAgICogQ29vcmRpbmF0ZXMgdG8gZGVjcmliZSBhIHJlY3Rhbmd1bGFyIHJlZ2lvbiBpbiBzcGFjZS5cbiAgICovXG4gIFRvb2xzaGVkLkdlb20uUmVjdCA9IGNsYXNzIHtcbiAgICAvKipcbiAgICAgKiBAcGFyYW0ge1Rvb2xzaGVkLlJlY3R8aW50fSBsZWZ0XG4gICAgICogIEVpdGhlciBhIGpRdWVyeSB3cmFwcGVyIG9yIFJlY3Qgb2JqZWN0IHRvIGJ1aWxkIHRoZSByZWN0YW5nbGUgd2l0aC5cbiAgICAgKiAgSWYganVzdCBhbiBpbnRlZ2VyLCB1c2UgdGhpcyB2YWx1ZSBhcyB0aGUgbGVmdCBlZGdlIG9mIHRoZSByZWN0YW5nbGUuXG4gICAgICogQHBhcmFtIHtpbnR9IHRvcFxuICAgICAqICBUaGUgY29vcmRpbmF0ZSBvZiB0aGUgdG9wIGVkZ2Ugb2YgdGhlIHJlY3RhbmdsZS5cbiAgICAgKiBAcGFyYW0ge2ludH0gcmlnaHRcbiAgICAgKiAgVGhlIGNvb3JkaW5hdGUgb2YgdGhlIHJpZ2h0IGVkZ2Ugb2YgdGhlIHJlY3RhbmdsZS5cbiAgICAgKiBAcGFyYW0ge2ludH0gYm90dG9tXG4gICAgICogIFRoZSBjb29yZGluYXRlIG9mIHRoZSBib3R0b20gZWRnZSBvZiB0aGUgcmVjdGFuZ2xlLlxuICAgICAqL1xuICAgIGNvbnN0cnVjdG9yKGxlZnQsIHRvcCwgcmlnaHQsIGJvdHRvbSkge1xuICAgICAgaWYgKGxlZnQgaW5zdGFuY2VvZiBUb29sc2hlZC5HZW9tLlJlY3QpIHtcbiAgICAgICAgT2JqZWN0LmFzc2lnbih0aGlzLCBsZWZ0KTtcbiAgICAgIH1cbiAgICAgIGVsc2Uge1xuICAgICAgICBpZiAodG9wIDw9IGJvdHRvbSkge1xuICAgICAgICAgIHRoaXMudG9wID0gdG9wO1xuICAgICAgICAgIHRoaXMuYm90dG9tID0gYm90dG9tO1xuICAgICAgICB9XG4gICAgICAgIGVsc2Uge1xuICAgICAgICAgIHRoaXMudG9wID0gYm90dG9tO1xuICAgICAgICAgIHRoaXMuYm90dG9tID0gdG9wO1xuICAgICAgICB9XG5cbiAgICAgICAgaWYgKGxlZnQgPD0gcmlnaHQpIHtcbiAgICAgICAgICB0aGlzLmxlZnQgPSBsZWZ0O1xuICAgICAgICAgIHRoaXMucmlnaHQgPSByaWdodDtcbiAgICAgICAgfVxuICAgICAgICBlbHNlIHtcbiAgICAgICAgICB0aGlzLmxlZnQgPSByaWdodDtcbiAgICAgICAgICB0aGlzLnJpZ2h0ID0gbGVmdDtcbiAgICAgICAgfVxuICAgICAgfVxuICAgIH1cblxuICAgIGdldFBvc2l0aW9uKCkge1xuICAgICAgcmV0dXJuIHsgbGVmdDogdGhpcy5sZWZ0LCB0b3A6IHRoaXMudG9wIH07XG4gICAgfVxuXG4gICAgZ2V0V2lkdGgoKSB7XG4gICAgICByZXR1cm4gdGhpcy5yaWdodCAtIHRoaXMubGVmdDtcbiAgICB9XG5cbiAgICBnZXRIZWlnaHQoKSB7XG4gICAgICByZXR1cm4gdGhpcy5ib3R0b20gLSB0aGlzLnRvcDtcbiAgICB9XG5cbiAgICBpc0luUmVjdChwdCkge1xuICAgICAgY29uc3QgcG9zID0gdGhpcy5nZXRQb3NpdGlvbigpO1xuICAgICAgcHQubGVmdCAtPSBwb3MubGVmdDtcbiAgICAgIHB0LnRvcCAtPSBwb3MudG9wO1xuXG4gICAgICByZXR1cm4gKHB0LmxlZnQgPj0gMCAmJiBwdC5sZWZ0IDw9IHRoaXMuZ2V0V2lkdGgoKSlcbiAgICAgICAgJiYgKHB0LnRvcCA+PSAwICYmIHB0LnRvcCA8PSB0aGlzLmdldEhlaWdodCgpKTtcbiAgICB9XG5cbiAgICBnZXRBcmVhKCkge1xuICAgICAgcmV0dXJuIHRoaXMuZ2V0V2lkdGgoKSAqIHRoaXMuZ2V0SGVpZ2h0KCk7XG4gICAgfVxuXG4gICAgb2Zmc2V0KHhPZmZzZXQsIHlPZmZzZXQpIHtcbiAgICAgIHRoaXMudG9wICs9IHlPZmZzZXQ7XG4gICAgICB0aGlzLmJvdHRvbSArPSB5T2Zmc2V0O1xuICAgICAgdGhpcy5sZWZ0ICs9IHhPZmZzZXQ7XG4gICAgICB0aGlzLnJpZ2h0ICs9IHhPZmZzZXQ7XG4gICAgfVxuXG4gICAgZ2V0SW50ZXJzZWN0aW9uKHJlY3QpIHtcbiAgICAgIGNvbnN0IG8xID0gcmVjdC5nZXRQb3NpdGlvbigpO1xuICAgICAgY29uc3QgbzIgPSB0aGlzLmdldFBvc2l0aW9uKCk7XG5cbiAgICAgIGNvbnN0IHggPSBNYXRoLm1heChvMS5sZWZ0LCBvMi5sZWZ0KTtcbiAgICAgIGNvbnN0IHkgPSBNYXRoLm1heChvMS50b3AsIG8yLnRvcCk7XG4gICAgICBjb25zdCByID0gTWF0aC5taW4obzEubGVmdCArIHJlY3QuZ2V0V2lkdGgoKSwgbzIubGVmdCArIHRoaXMuZ2V0V2lkdGgoKSk7XG4gICAgICBjb25zdCBiID0gTWF0aC5taW4obzEudG9wICsgcmVjdC5nZXRIZWlnaHQoKSwgbzIudG9wICsgdGhpcy5nZXRIZWlnaHQoKSk7XG5cbiAgICAgIC8vIENoZWNrIHRoYXQgdGhpcyBwb2ludCBpcyBpbiB0aGUgcmVjdGFuZ2xlLlxuICAgICAgcmV0dXJuICh4ID4gciB8fCB5ID4gYikgPyBudWxsIDogbmV3IFRvb2xzaGVkLkdlb20uUmVjdCh4LCB5LCByLCBiKTtcbiAgICB9XG5cbiAgICBjb250YWlucyhyZWN0KSB7XG4gICAgICBjb25zdCBhID0gcmVjdC5nZXRQb3NpdGlvbigpO1xuICAgICAgY29uc3QgYiA9IHRoaXMuZ2V0UG9zaXRpb24oKTtcblxuICAgICAgcmV0dXJuIGEubGVmdCA+PSBiLmxlZnQgJiYgYS5sZWZ0ICsgcmVjdC5nZXRXaWR0aCgpIDw9IGIubGVmdCArIHRoaXMuZ2V0V2lkdGgoKVxuICAgICAgICAmJiBhLnRvcCA+PSBiLnRvcCAmJiBhLnRvcCArIHJlY3QuZ2V0SGVpZ2h0KCkgPD0gYi50b3AgKyB0aGlzLmdldEhlaWdodCgpO1xuICAgIH1cbiAgfTtcbn0pKERydXBhbCk7XG4iXX0=
