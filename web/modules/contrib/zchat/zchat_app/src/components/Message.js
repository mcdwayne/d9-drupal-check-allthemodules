import React from 'react';
import ReactDOM from 'react-dom';

class Message extends React.Component {
  render() {
    const rowClass = drupalSettings.user.uid == this.props.author_uid ? 'sent' : 'received';
    const author = drupalSettings.user.uid == this.props.author_uid ? Drupal.t('you') : this.props.author_name;
    return (
    <div className={"z-message-row " + rowClass}>
        <div className="z-message-author">
          {author}
        </div>
        <div className="z-message-text">
          {this.props.message_text}
        </div>
        <div className="z-message-created">
          {this.props.message_created}
        </div>
     </div>
    );
  }
}

export default Message;