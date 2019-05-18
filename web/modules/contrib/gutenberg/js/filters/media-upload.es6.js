((wp, Drupal, DrupalGutenberg, $) => {
  const { hooks, element } = wp;
  const { addFilter } = hooks;
  const { Component } = element;
  const { MediaBrowser } = DrupalGutenberg.Components;
  const __ = Drupal.t;

  class MediaUpload extends Component {
    constructor() {
      super(...arguments);
      this.onSelect = this.onSelect.bind(this);
      this.onClose = this.onClose.bind(this);
      this.openModal = this.openModal.bind(this);
    }

    componentWillUnmount() {
      delete this.frame;
    }

    onSelect(medias) {
      const { multiple, onSelect } = this.props;

      // Adding this try-catch because of a know issue with inline-images.
      onSelect(multiple ? medias : medias[0]);
      this.frame.close();
      // this.onClose();
    }

    onClose() {
      const { onClose } = this.props;

      document.getElementById('media-browser-modal').remove();

      if (onClose) {
        onClose();
      }
    }

    openModal() {
      const { multiple, allowedTypes, value } = this.props;
      const mediaBrowser = document.createElement('div');

      mediaBrowser.setAttribute('id', 'media-browser-modal');
      element.render(
        <MediaBrowser
          multiple={multiple}
          allowedTypes={allowedTypes}
          value={value}
          onSelect={this.onSelect}
        />,
        mediaBrowser,
        () => {
          this.frame = Drupal.dialog(mediaBrowser, {
            title: __('Media library'),
            width: '95%',
            height: document.documentElement.clientHeight - 100,
            buttons: {
              // This is mainly a placeholder button to force the dialog
              // to create a buttonset pane. Not really used.
              [__('Cancel')]: () => {
                this.frame.close();
              },
            },
            close: this.onClose,
            create: event => {
              // Move buttons inside the Media Browser component to dialog buttons pane.
              const $buttons = $(event.target).find('.form-actions');
              const $dialogButtons = $buttons
                .closest('.ui-dialog')
                .find('.ui-dialog-buttonpane');

              $dialogButtons.empty();
              $dialogButtons.append($buttons);
            },
          });

          this.frame.showModal();
        },
      );
    }

    render() {
      const { render } = this.props;
      return render({ open: this.openModal });
    }
  }

  addFilter(
    'editor.MediaUpload',
    'core/edit-post/components/media-upload/replace-media-upload',
    () => MediaUpload,
  );
})(wp, Drupal, DrupalGutenberg, jQuery);
