(function (Drupal) {

  /**
   * @class Html
   *
   * The partial JavaScript counterpart of \Drupal\Component\Utility\Html.
   *
   * Does not contain the DOM traversals or AJAX methods.
   */
  class Html {

    /**
     * An array of previously cleaned HTML classes.
     *
     * @var {Object}
     */
    static classes = {};

    /**
     * An array of the initial IDs used in one request.
     *
     * @var {Object}
     */
    static seenIdsInit = null;

    /**
     * An array of IDs, including incremented versions when an ID is duplicated.
     *
     * @var {Object}
     */
    static seenIds = null;

    /**
     * Prepares a string for use as a CSS identifier (element, class, or ID name).
     *
     * Note: this is essentially a direct copy from
     * \Drupal\Component\Utility\Html::cleanCssIdentifier
     *
     * @param {String} identifier
     *   The identifier to clean.
     * @param {Object} [filter]
     *   An object of string replacements to use on the identifier.
     *
     * @return {String}
     *   The cleaned identifier.
     */
    static cleanCssIdentifier(identifier, filter) {
      filter = filter || {
        ' ': '-',
        '_': '-',
        '/': '-',
        '[': '-',
        ']': ''
      };

      if (filter['__'] === void 0) {
        identifier = identifier.replace('__', '#DOUBLE_UNDERSCORE#', identifier);
      }

      identifier = identifier.replace(Object.keys(filter), Object.values(filter), identifier);

      if (filter['__'] === void 0) {
        identifier = identifier.replace('#DOUBLE_UNDERSCORE#', '__', identifier);
      }

      identifier = identifier.replace(/[^\u002D\u0030-\u0039\u0041-\u005A\u005F\u0061-\u007A\u00A1-\uFFFF]/g, '');
      identifier = identifier.replace(['/^[0-9]/', '/^(-[0-9])|^(--)/'], ['_', '__'], identifier);

      return identifier;
    }

    /**
     * Decodes all HTML entities including numerical ones to regular UTF-8 bytes.
     *
     * Double-escaped entities will only be decoded once ("&amp;lt;" becomes
     * "&lt;", not "<").
     *
     * Double-escaped entities will only be decoded once ("&amp;lt;" becomes
     * "&lt;", not "<"). Be careful when using this function, as it will revert
     * previous sanitization efforts (&lt;script&gt; will become <script>).
     *
     * This method is not the opposite of Html.escape. For example, this method
     * will convert "&eacute;" to "é", whereas Html::escape() will not convert
     * "é" to "&eacute;".
     *
     * @param {String} text
     *   The text to decode entities in.
     *
     * @return string
     *   The input $text, with all HTML entities decoded once.
     *
     * @see \Drupal\Component\Utility\Html::decodeEntities
     * @see Html.escape
     * @see https://stackoverflow.com/a/7394787
     *
     * @todo Consider replacing with https://www.npmjs.com/package/html-entities
     *   once jsdelivr support is added.
     */
    static decodeEntities(text) {
      const el = document.createElement('textarea');
      el.innerHTML = text;
      return el.value;
    }

    /**
     * Escapes text by converting special characters to HTML entities.
     *
     * This method escapes HTML for sanitization purposes by replacing the
     * following special characters with their HTML entity equivalents:
     * - & (ampersand) becomes &amp;
     * - " (double quote) becomes &quot;
     * - ' (single quote) becomes &#039;
     * - < (less than) becomes &lt;
     * - > (greater than) becomes &gt;
     * Special characters that have already been escaped will be double-escaped
     * (for example, "&lt;" becomes "&amp;lt;").
     *
     * This method is not the opposite of Html.decodeEntities. For example,
     * this method will not encode "é" to "&eacute;", whereas
     * Html::decodeEntities() will convert all HTML entities to UTF-8 bytes,
     * including "&eacute;" and "&lt;" to "é" and "<".
     *
     * @param {String} text
     *   The input text.
     *
     * @return {String}
     *   The text with all HTML special characters converted.
     *
     * @see htmlspecialchars()
     * @see \Drupal\Component\Utility\Html::decodeEntities()
     *
     * @todo Consider replacing with https://www.npmjs.com/package/html-entities
     *   once jsdelivr support is added.
     */
    static escape(text) {
      // Immediately return an empty string if there is no text.
      return text === void 0 || text === null ? '' : text.toString()
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
    }

    /**
     * Prepares a string for use as a valid class name.
     *
     * Do not pass one string containing multiple classes as they will be
     * incorrectly concatenated with dashes, i.e. "one two" will become "one-two".
     *
     * @param {*} className
     *   The class name to clean. It can be a string or anything that can be cast
     *   to string.
     *
     * @return {String}
     *   The cleaned class name.
     */
    static getClass(className) {
      className = '' + className;
      if (this.classes[className] === void 0) {
        this.classes[className] = this.cleanCssIdentifier(className.toLowerCase());
      }
      return this.classes[className];
    }

    /**
     * Prepares a string for use as a valid HTML ID and guarantees uniqueness.
     *
     * @param {String} id
     *   The ID to clean.
     *
     * @return {String}
     *   The cleaned ID.
     */
    static getUniqueId(id) {
      if (this.seenIdsInit === null) {
        this.seenIdsInit = {};
      }
      if (this.seenIds === null) {
        this.seenIds = this.seenIdsInit;
      }

      id = this.getId(id);

      if (this.seenIds[id] !== void 0) {
        id = id + '--' + ++this.seenIds[id];
      }
      else {
        this.seenIds[id] = 1;
      }
      return id;
    }

    /**
     * Prepares a string for use as a valid HTML ID.
     *
     * @param {String} id
     *   The ID to clean.
     *
     * @return {String}
     *   The cleaned ID.
     *
     * @see this.getUniqueId
     */
    static getId(id) {
      return id
        .toLowerCase()
        .replace(']', '').replace(/\s|_|\[/, '-')
        .replace(/[^A-Za-z0-9\-_]/, '')
        .replace(/[-]+/, '-');
    }

    /**
     * Resets the list of seen IDs.
     */
    static resetSeenIds() {
      this.seenIds = null;
    }

  }

  Drupal.Html = Html;

  // Replace core's "checkPlain" method.
  Drupal.checkPlain = Drupal.Html.escape;

})(window.Drupal);
