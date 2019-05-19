import React from "react";
import ReactDOM from "react-dom";

class Greeting extends React.Component {

  render() {
    return (
      <div>
        {this.props.text}
      </div>
    );
  }

}

ReactDOM.render(<Greeting text="Hi!" />, document.getElementById('page'));
