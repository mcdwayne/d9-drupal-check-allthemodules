/* eslint no-bitwise: ["error", { "allow": ["|"] }] */
(({ Toolshed }) => {
  Toolshed.Geom = {};

  /**
   * Defines a enumerations for different edges.
   *
   * @type {Object}
   */
  Toolshed.Geom.Edges = class {
    static get TOP() {
      return 0x01;
    }

    static get BOTTOM() {
      return 0x02;
    }

    static get LEFT() {
      return 0x10;
    }

    static get RIGHT() {
      return 0x20;
    }
  };

  /**
   * Defines a directions enumerations or mask.
   *
   * @type {Object}
   */
  Toolshed.Geom.Direction = class {
    static get VERTICAL() {
      return Toolshed.Geom.Edges.TOP | Toolshed.Geom.Edges.BOTTOM;
    }

    static get HORIZONTAL() {
      return Toolshed.Geom.Edges.LEFT | Toolshed.Geom.Edges.RIGHT;
    }

    static get ANY() {
      return Toolshed.Geom.Direction.HORIZONTAL | Toolshed.Geom.Direction.VERTICAL;
    }
  };

  /**
   * Coordinates to decribe a rectangular region in space.
   */
  Toolshed.Geom.Rect = class {
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
    constructor(left, top, right, bottom) {
      if (left instanceof Toolshed.Geom.Rect) {
        Object.assign(this, left);
      }
      else {
        if (top <= bottom) {
          this.top = top;
          this.bottom = bottom;
        }
        else {
          this.top = bottom;
          this.bottom = top;
        }

        if (left <= right) {
          this.left = left;
          this.right = right;
        }
        else {
          this.left = right;
          this.right = left;
        }
      }
    }

    getPosition() {
      return { left: this.left, top: this.top };
    }

    getWidth() {
      return this.right - this.left;
    }

    getHeight() {
      return this.bottom - this.top;
    }

    isInRect(pt) {
      const pos = this.getPosition();
      pt.left -= pos.left;
      pt.top -= pos.top;

      return (pt.left >= 0 && pt.left <= this.getWidth())
        && (pt.top >= 0 && pt.top <= this.getHeight());
    }

    getArea() {
      return this.getWidth() * this.getHeight();
    }

    offset(xOffset, yOffset) {
      this.top += yOffset;
      this.bottom += yOffset;
      this.left += xOffset;
      this.right += xOffset;
    }

    getIntersection(rect) {
      const o1 = rect.getPosition();
      const o2 = this.getPosition();

      const x = Math.max(o1.left, o2.left);
      const y = Math.max(o1.top, o2.top);
      const r = Math.min(o1.left + rect.getWidth(), o2.left + this.getWidth());
      const b = Math.min(o1.top + rect.getHeight(), o2.top + this.getHeight());

      // Check that this point is in the rectangle.
      return (x > r || y > b) ? null : new Toolshed.Geom.Rect(x, y, r, b);
    }

    contains(rect) {
      const a = rect.getPosition();
      const b = this.getPosition();

      return a.left >= b.left && a.left + rect.getWidth() <= b.left + this.getWidth()
        && a.top >= b.top && a.top + rect.getHeight() <= b.top + this.getHeight();
    }
  };
})(Drupal);
