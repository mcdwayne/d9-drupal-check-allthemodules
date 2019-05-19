import React from 'react';
import ReactDOM from 'react-dom';

import 'emoji-mart/css/emoji-mart.css'
import { Picker } from 'emoji-mart'
import { Emoji } from 'emoji-mart'

class CreateMessageForm extends React.Component {
  state = {
    message: '',
    showEmoji: 0,
  };

  handleSubmit = () => {
    if (this.state.message != '') {
      this.props.onFormSubmit(this.state.message);
      this.setState({ message: ''});
    }
  };

  showEmoji = () => {
    if (this.state.showEmoji == 0) {
      this.setState({ showEmoji: 1});
    }
    else {
      this.setState({ showEmoji: 0});
    }
  };

  handleMessageChange = (e) => {
    this.setState({ message: e.target.value });
  };

  handleKeyPress = (e) => {
    var code = e.keyCode || e.which;
    if (code === 13) {
      this.handleSubmit();
    }
  };

  addEmoji = (e) => {
    this.setState({
      message: this.state.message + e.native  ,
      showEmoji: 0,
    });
    this.refs.zchatInput.focus();
  };

  render() {
    return (
      <div className='z-message-form'>
        <div className='z-message-form-input'>
          <input
            type='text'
            ref="zchatInput"
            value={this.state.message}
            onChange={this.handleMessageChange}
            onKeyPress={this.handleKeyPress}
          />
        </div>
        <div className='z-form-emoji-button'>
          <Emoji
             emoji={{ id: 'smiley' }} size={16}
             onClick={this.showEmoji}
          />
        </div>
        <div className='z-message-form-button'>
          <button
            className='z-form-button'
            onClick={this.handleSubmit}
          >
           {Drupal.t('Send')}
          </button>
        </div>
        <div className='zz-form-emoji-picker'>
        { this.state.showEmoji == 1 ?
            <Picker
              style={{position: 'absolute', top: '80px', right: '20px' }}
              title={Drupal.t('Pick your emoji...')}
              onSelect={this.addEmoji}
            /> : null }
        </div>
      </div>
    );
  }
}

export default CreateMessageForm;