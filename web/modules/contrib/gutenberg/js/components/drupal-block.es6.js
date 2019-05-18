/* eslint func-names: ["error", "never"] */
(function(wp, Drupal) {
  const { data } = wp;
  const { withSelect } = data;

  class DrupalBlock extends wp.element.Component {
    render() {
      if (this.props.blockContent) {
        return (
          <div>
            <div className={this.props.className} dangerouslySetInnerHTML={{__html: this.props.blockContent.html}} />
          </div>
        );
      }

      return(
        <div className="loading-drupal-block">{Drupal.t('Loading')}...</div>
      );
    }
  }

  const createClass = withSelect((select, props) => {
    const { getBlock } = select('drupal');
    const { id } = props;
    const block = getBlock(id);
    const node = document.createElement('div');

    if (block && block.html) {
      node.innerHTML = block.html;
      const formElements = node.querySelectorAll('input, select, button, textarea');
      formElements.forEach(element => {
        element.setAttribute('readonly', true);
        element.setAttribute('required', false);
      });
    }

    return {
      blockContent: { html: node.innerHTML }//getBlock(id), // `/editor/blocks/load/${blockId}`
    };
  })(DrupalBlock);

  window.DrupalGutenberg = window.DrupalGutenberg || {};
  window.DrupalGutenberg.Components = window.DrupalGutenberg.Components || {}
  window.DrupalGutenberg.Components.DrupalBlock = createClass;
})(wp, Drupal);
