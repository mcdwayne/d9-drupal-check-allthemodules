import BaseComponent from '../BaseComponent';

class EntityList extends BaseComponent {
  render() {
    var design = this.props.design;
    if (design != undefined) {
      return (
        <div className="container">
          <div className="row">
            Entity List
          </div>
        </div>
      );
    }
    else {
      return null;
    }
  }
}

export default EntityList;
