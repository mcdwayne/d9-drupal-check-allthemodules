import React from 'react';
import ReactDOM from 'react-dom';

import MessageList from './MessageList';

class ChatMainWindow extends React.Component {
  constructor(props) {
    super(props);
    this.loadNewMessagesFromServer = this.loadNewMessagesFromServer.bind(this);
    this.mergeNewMessage = this.mergeNewMessage.bind(this);
  };

  state = {
    messages: [],
    newest_message_time: 0,
    oldest_message_time: 0,
    moreIsLoading: 0,
  };

  componentDidMount() {
    this.loadNewMessagesFromServer(1);
    setInterval(this.loadNewMessagesFromServer, drupalSettings.zchat.zchat_message_refresh_interval);
  }

  mergeNewMessage = (localMessage, serverMessage) => {
    localMessage.message_id = serverMessage.id[0].value.toString();
    this.setState({
      messages: [localMessage].concat(this.state.messages),
    });
  };

  loadNewMessagesFromServer(updateOldestMeesage) {
    if (updateOldestMeesage == 1) {
      client.getNewMessages(this.state.newest_message_time, (serverMessages) => (
          this.setState({
            // Merge the messages, but eliminate duplicate entries.
            messages: serverMessages.concat(this.state.messages).filter((thing, index, self) =>
              index === self.findIndex((t) => (
                t.message_id === thing.message_id
              ))
            ).sort(function(a, b){
                if(a.message_created_time > b.message_created_time) return -1;
                if(a.message_created_time < b.message_created_time) return 1;
                return 0;
            }),
            newest_message_time: helpers.getNewestMessageFromList(serverMessages) != 0 ? helpers.getNewestMessageFromList(serverMessages) : this.state.newest_message_time,
            oldest_message_time: helpers.getOldestMessageFromList(serverMessages) != 0 ? helpers.getOldestMessageFromList(serverMessages) : this.state.oldest_message_time,
          })
        ));
    }
    else {
      client.getNewMessages(this.state.newest_message_time, (serverMessages) => (
          this.setState({
            // Merge the messages, but eliminate duplicate entries.
            messages: serverMessages.concat(this.state.messages).filter((thing, index, self) =>
              index === self.findIndex((t) => (
                t.message_id === thing.message_id
              ))
            ).sort(function(a, b){
                if(a.message_created_time > b.message_created_time) return -1;
                if(a.message_created_time < b.message_created_time) return 1;
                return 0;
            }),
            newest_message_time: helpers.getNewestMessageFromList(serverMessages) != 0 ? helpers.getNewestMessageFromList(serverMessages) : this.state.newest_message_time,
          })
        ));
    }
  };

  handleMoreMessages = () => {
    if (this.state.moreIsLoading == 0) {
      this.setState({
        moreIsLoading: 1
      });
      client.getMoreMessages(this.state.oldest_message_time, (serverMessages) => (
          this.setState({
            // Merge the messages, but eliminate duplicate entries.
            messages: serverMessages.concat(this.state.messages).filter((thing, index, self) =>
              index === self.findIndex((t) => (
                t.message_id === thing.message_id
              ))
            ).sort(function(a, b){
                if(a.message_created_time > b.message_created_time) return -1;
                if(a.message_created_time < b.message_created_time) return 1;
                return 0;
            }),
            oldest_message_time: helpers.getOldestMessageFromList(serverMessages) != 0 ? helpers.getOldestMessageFromList(serverMessages) : this.state.oldest_message_time,
            moreIsLoading: 0
          })
        )
      );
    }
  };

  createMessage = (message) => {
    const m = helpers.newMessage(message);
    client.createMessage(m.message_server, (message) => (
      this.mergeNewMessage(m.message_local, message)
    ));
  };

  handleCreateFormSubmit = (message) => {
    this.createMessage(message);
  };

  render() {
    return (
      <div className="zchat-container">
        <div>
          <MessageList
            messages={this.state.messages}
            onFormSubmit={this.handleCreateFormSubmit}
            onMoreMessages={this.handleMoreMessages}
          />
        </div>
      </div>
    );
  }
}

export default ChatMainWindow;