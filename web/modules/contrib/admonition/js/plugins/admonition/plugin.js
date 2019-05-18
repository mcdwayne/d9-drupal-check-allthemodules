/**
 * Created by kieran on 8/5/16.
 */

(function ($) {
  Drupal.settings = Drupal.settings || {};
  Drupal.settings.admonition = Drupal.settings.admonition || {};
  Drupal.settings.admonition.DEFAULT_TYPE = 'note';
  Drupal.settings.admonition.DEFAULT_ALIGNMENT = 'center';
  Drupal.settings.admonition.DEFAULT_WIDTH = 'half';
  CKEDITOR.plugins.add('admonition', {
    requires: 'widget',
    icons: 'admonition',
    init: function (editor) {
      var path = this.path;
      editor.addContentsCss( this.path + 'css/base.css' );
      // editor.addContentsCss( this.path + 'css/extra.css' );
      // editor.addContentsCss( this.path + 'css/hint.css' );
      // editor.addContentsCss( this.path + 'css/note.css' );
      // editor.addContentsCss( this.path + 'css/troubleshoot.css' );
      // editor.addContentsCss( this.path + 'css/warning.css' );
      editor.widgets.add('admonition', {
        path: path,
        button: 'Add an admonition to the reader',
        dialog: 'admonition',
        //Get the HTML template from the editor object.
        template: editor.config.admonition_template,
        //Define the editable pieces of the template.
        editables: {
          content: {
            selector: 'div.admonition-content'
          }
        },
        //Add HTML to content that ACF will allow.
        allowedContent:
            'div(!admonition,!admonition-*);'
              + 'img(!admonition-*);'
              + 'div(!admonition-content);',
        //What HTML the widget requires.
        requiredContent: 'div(admonition);',
        init: function() {
          //Admonition type.
          if ( this.element.hasClass( 'admonition-extra' ) ) {
            this.setData('type', 'extra');
          }
          else if ( this.element.hasClass( 'admonition-hint' ) ) {
            this.setData('type', 'hint');
          }
          else if ( this.element.hasClass( 'admonition-note' ) ) {
            this.setData('type', 'note');
          }
          else if ( this.element.hasClass( 'admonition-troubleshoot' ) ) {
            this.setData('type', 'troubleshoot');
          }
          else if ( this.element.hasClass( 'admonition-warning' ) ) {
            this.setData('type', 'warning');
          }
          else {
            this.setData('type', Drupal.settings.admonition.DEFAULT_TYPE);
          }
          //Width
          if ( this.element.hasClass( 'admonition-quarter' ) ) {
            this.setData('width', 'quarter');
          }
          else if ( this.element.hasClass( 'admonition-half' ) ) {
            this.setData('width', 'half');
          }
          else if ( this.element.hasClass( 'admonition-full' ) ) {
            this.setData('width', 'full');
          }
          else {
            this.setData('width', Drupal.settings.admonition.DEFAULT_WIDTH);
          }
          //Alignment
          if ( this.element.hasClass( 'admonition-left' ) ) {
            this.setData('alignment', 'left');
          }
          else if ( this.element.hasClass( 'admonition-right' ) ) {
            this.setData('alignment', 'right');
          }
          else if ( this.element.hasClass( 'admonition-center' ) ) {
            this.setData('alignment', 'center');
          }
          else {
            this.setData('alignment', Drupal.settings.admonition.DEFAULT_ALIGNMENT);
          }

        }, //End init().
        /**
         * Called when initialing widget display in CK, and when
         * data is returned by the dialog.
         */
        data: function() {
          //Get the icon element.
          var icon = this.element.find('img').getItem(0);
          //Remove existing classes, styles, and attributes.
          //Width.
          this.element
            .removeClass( 'admonition-quarter' )
            .removeClass( 'admonition-half' )
            .removeClass( 'admonition-full' );
          //Alignment.
          this.element
            .removeClass( 'admonition-left' )
            .removeClass( 'admonition-right' )
            .removeClass( 'admonition-center' );
          //Type
          this.element.removeClass( 'admonition-extra' )
            .removeClass( 'admonition-hint' )
            .removeClass( 'admonition-note' )
            .removeClass( 'admonition-troubleshoot' )
            .removeClass( 'admonition-warning' );
          //Icon image.
          if ( icon ) {
            icon.removeAttributes(['src', 'alt', 'title']);
          }
          //Add new stuff.
          if ( this.data.width ) {
            this.element.addClass('admonition-' + this.data.width);
          }
          if ( this.data.alignment ) {
            this.element.addClass('admonition-' + this.data.alignment);
          }
          if ( this.data.type ) {
            this.element.addClass('admonition-' + this.data.type);
            //Add type-specific CSS.
            editor.addContentsCss( this.path + 'css/' + this.data.type + '.css' );
          }
          if ( icon && this.data.type ) {
            //Show the right icon.
            icon.setAttribute('src', this.path + 'icons/' + this.data.type + '.png')
                .setAttribute('title', capitalizeFirstLetter(this.data.type))
                .setAttribute('alt', capitalizeFirstLetter(this.data.type))
                .addClass('admonition-icon');
          }
        }, //End data function.
        /**
         * Convert from storage representation to display representation.
         */
        upcast: function( element ) {
          if ( ! element.attributes || element.attributes['data-chunk-type'] != 'admonition') {
            return;
          }
          //What are the attributes of this admonition instance?
          var admonitionType =
            element.attributes['data-admonition-type']
              ? element.attributes['data-admonition-type']
              : Drupal.settings.admonition.DEFAULT_TYPE;
          var admonitionAlignment =
              element.attributes['data-admonition-alignment']
                  ? element.attributes['data-admonition-alignment']
                  : Drupal.settings.admonition.DEFAULT_ALIGNMENT;
          var admonitionWidth =
              element.attributes['data-admonition-width']
                  ? element.attributes['data-admonition-width']
                  : Drupal.settings.admonition.DEFAULT_WIDTH;
          var content = element.getHtml();
          //Change the element to display rep.
          element.addClass('admonition');
          element.addClass('admonition-' + admonitionType);
          element.addClass('admonition-' + admonitionAlignment);
          element.addClass('admonition-' + admonitionWidth);
          var widgetPath = editor.widgets.registered.admonition.path;
          element.setHtml(
                '<img class="admonition-icon" src="'
              +    widgetPath + 'icons/' + admonitionType + '.png" '
              +    'alt="' + capitalizeFirstLetter(admonitionType) + '" '
              +    'title="' + capitalizeFirstLetter(admonitionType) + '" '
              + '>'
              + '<div class="admonition-content">'
              +   content
              + '</div>'
          );
          return element;
        }, //End upcast.
        /**
         * Convert from display representation to storage representation.
         */
        downcast: function(element) {
          if (!element.hasClass('admonition')) {
            return;
          }
          var content = element.getFirst(
            function(el) {
              return el.hasClass('admonition-content');
            }
          ).getHtml();
          //Infer admonition type from class.
          var admonitionType = Drupal.settings.admonition.DEFAULT_TYPE;
          if ( element.hasClass('admonition-extra') ) {
            admonitionType = 'extra';
          }
          else if ( element.hasClass('admonition-hint') ) {
            admonitionType = 'hint';
          }
          else if ( element.hasClass('admonition-note') ) {
            admonitionType = 'note';
          }
          else if ( element.hasClass('admonition-troubleshoot') ) {
            admonitionType = 'troubleshoot';
          }
          else if ( element.hasClass('admonition-warning') ) {
            admonitionType = 'warning';
          }
          //Infer alignment.
          var admonitionAlignment = Drupal.settings.admonition.DEFAULT_ALIGNMENT;
          if ( element.hasClass('admonition-left') ) {
            admonitionAlignment = 'left';
          }
          else if ( element.hasClass('admonition-center') ) {
            admonitionAlignment = 'center';
          }
          else if ( element.hasClass('admonition-right') ) {
            admonitionAlignment = 'right';
          }
          //Infer width.
          var admonitionWidth = Drupal.settings.admonition.DEFAULT_WIDTH;
          if ( element.hasClass('admonition-full') ) {
            admonitionWidth = 'full';
          }
          else if ( element.hasClass('admonition-half') ) {
            admonitionWidth = 'half';
          }
          else if ( element.hasClass('admonition-quarter') ) {
            admonitionWidth = 'quarter';
          }
          //Storage representation doesn't have classes, so...
          delete element.attributes.class;
          //Set data attributes.
          element.attributes['data-chunk-type'] = 'admonition';
          element.attributes['data-admonition-type'] = admonitionType;
          element.attributes['data-admonition-alignment'] = admonitionAlignment;
          element.attributes['data-admonition-width'] = admonitionWidth;
          element.setHtml(content);
          return element;
        }
      });
      editor.ui.addButton( 'admonition', {
        label: 'Admonition',
        command: 'admonition'
      } );
      CKEDITOR.dialog.add( 'admonition', this.path + 'dialogs/admonition.js' );
      function capitalizeFirstLetter(value) {
        return value.charAt(0).toUpperCase() + value.slice(1);
      }
    }
  });

})(jQuery);
