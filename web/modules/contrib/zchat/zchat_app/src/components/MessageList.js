import React from 'react';
import ReactDOM from 'react-dom';

import Message from './Message';
import CreateMessageForm from './CreateMessageForm';

class MessageList extends React.Component {
  handleSubmit = (message) => {
    this.props.onFormSubmit(message);
  };

  handleMoreMessages = () => {
    this.props.onMoreMessages();
  };

  componentDidMount() {
    this.refs.zchatMessages.addEventListener('scroll', this.onScroll, false);
  }

  componentWillUnmount() {
    this.refs.zchatMessages.removeEventListener('scroll', this.onScroll, false);
  }

  onScroll = () => {
    if (this.refs.zchatMessages.scrollTop + drupalSettings.zchat.zchat_load_more_offeset > this.refs.zchatMessages.scrollHeight) {
      this.handleMoreMessages();
    }
  }

  render() {
    const messages = this.props.messages.map((message) => (
      <Message
        key={message.message_id}
        id={message.message_id}
        message_text={message.message_text}
        author_uid={message.author_uid}
        author_name={message.author_name}
        message_created={message.message_created}
      />
    ));
    return (
      <div>
        <CreateMessageForm
          onFormSubmit={this.handleSubmit}
        />
        <div className="zchat-messages" ref="zchatMessages">
          {messages}
        </div>
      </div>
    );
  }
}

export default MessageList;