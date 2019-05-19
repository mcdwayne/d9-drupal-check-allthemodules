(function () {

    CKEDITOR.plugins.add('ukbutton', {
            lang: 'en,ru',
            requires: 'widget,dialog',
            icons: 'ukbutton',
            init: function (editor) {
                var lang = editor.lang.ukbutton;

                CKEDITOR.dialog.add('ukbutton', this.path + 'dialogs/ukbutton.js');

                // Add widget
                editor.ui.addButton('ukbutton', {
                    label: lang.buttonTitle,
                    command: 'ukbutton',
                    icon: this.path + 'icons/ukbutton.png'
                });

                editor.widgets.add('ukbutton', {
                    dialog: 'ukbutton',

                    init: function () {

                    },

                    template: '<a class="uk-button">' + '<span class="text"></span>' + '</a>',

                    data: function () {
                        var $el = jQuery(this.element.$);

                        if (this.data.btntype) {
                            $el.removeClass('uk-button-link uk-button-primary uk-button-success uk-button-danger').addClass(this.data.btntype);
                        }

                        $el.removeClass('uk-button-mini uk-button-small uk-button-large');
			if (this.data.btnsize) {
                            $el.addClass(this.data.btnsize);
                        }

                        if (this.data.href) {
                            $el.attr('href', this.data.href);
                        }

                        if (this.data.target && this.data.target != '') {
                            $el.attr('target', this.data.target);
                        }

                        if (this.data.text) {
                            jQuery('.text', $el).text(this.data.text);
                        }

                        if (this.data.hasOwnProperty('bsiconleft')) {
                            jQuery('.bs-icon-left', $el).remove();
                            if (this.data.bsiconleft) {
                                $el.prepend('<span class="bs-icon-left glyphicon ' + this.data.bsiconleft + '"></span>');
                            }
                        }

                        if (this.data.hasOwnProperty('bsiconright')) {
                            jQuery('.bs-icon-right', $el).remove();
                            if (this.data.bsiconright) {
                                $el.append('<span class="bs-icon-right glyphicon ' + this.data.bsiconright + '"></span>');
                            }
                        }

                        if (this.data.hasOwnProperty('faiconleft')) {
                            jQuery('.fa-icon-left', $el).remove();
                            if (this.data.faiconleft) {
                                $el.prepend('<i class="fa fa-icon-left ' + this.data.faiconleft + '"></i>');
                            }
                        }

                        if (this.data.hasOwnProperty('faiconright')) {
                            jQuery('.fa-icon-right', $el).remove();
                            if (this.data.faiconright) {
                                $el.append('<i class="fa fa-icon-right ' + this.data.faiconright + '"></i>');
                            }
                        }
                    },

                    requiredContent: 'a(uk-button)',

                    upcast: function (element) {
                        return element.name == 'a' && element.hasClass('uk-button');
                    }
                });
            }
        }
    );

})();






