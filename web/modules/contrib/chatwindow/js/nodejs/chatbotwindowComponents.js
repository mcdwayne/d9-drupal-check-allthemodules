var $ = require("jquery");
var React = require('react');
var ReactDOM = require('react-dom');



"use strict";

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _possibleConstructorReturn(self, call) { if (!self) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return call && (typeof call === "object" || typeof call === "function") ? call : self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function, not " + typeof superClass); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, enumerable: false, writable: true, configurable: true } }); if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass; }

var Chatboticon = function (_React$Component) {
	_inherits(Chatboticon, _React$Component);

	function Chatboticon(props) {
		_classCallCheck(this, Chatboticon);

		var _this = _possibleConstructorReturn(this, (Chatboticon.__proto__ || Object.getPrototypeOf(Chatboticon)).call(this, props));

		_this.state = {};

		_this.togglepopupwindow = _this.togglepopupwindow.bind(_this);

		return _this;
	}

	_createClass(Chatboticon, [{
		key: "togglepopupwindow",
		value: function togglepopupwindow(e) {

			e.preventDefault();
			$("#chatwindowchat").toggle();
		}
	}, {
		key: "render",
		value: function render() {

			return React.createElement(
				"a",
				{ href: "#", onClick: this.togglepopupwindow },
				React.createElement("img", { src: window.baseurlforchat + window.modulepathchatwindow + "/images/chat_icon.png", alt: "chaticon" })
			);
		}
	}]);

	return Chatboticon;
}(React.Component);

var Chatbotwindow = function (_React$Component2) {
	_inherits(Chatbotwindow, _React$Component2);

	function Chatbotwindow(props) {
		_classCallCheck(this, Chatbotwindow);

		var _this2 = _possibleConstructorReturn(this, (Chatbotwindow.__proto__ || Object.getPrototypeOf(Chatbotwindow)).call(this, props));

		_this2.state = {
			chatdata: [],
			boterror: ''
		};

		_this2.chatuserinput = React.createRef();
		_this2.chatdatauserserver = React.createRef();
		_this2.postuserchatdata = _this2.postuserchatdata.bind(_this2);
		_this2.renderuserchatdata = _this2.renderuserchatdata.bind(_this2);
		_this2.handleKeyPress = _this2.handleKeyPress.bind(_this2);
		return _this2;
	}

	_createClass(Chatbotwindow, [{
		key: "togglepopupwindow",
		value: function togglepopupwindow(e) {

			e.preventDefault();
			$("#chatwindowchat").toggle();
		}
	}, {
		key: "renderuserchatdata",
		value: function renderuserchatdata(data) {

			return React.createElement(Userchatdata, { userchat: data });
		}
	}, {
		key: "renderbotchatdata",
		value: function renderbotchatdata(data) {

			return React.createElement(Rasaresponsedata, { botreply: data });
		}
	}, {
		key: "scrollToBottom",
		value: function scrollToBottom() {

			this.chatdatauserserver.current.scrollTop = this.chatdatauserserver.current.scrollHeight;
		}
	}, {
		key: "componentDidMount",
		value: function componentDidMount() {
			this.scrollToBottom();
		}
	}, {
		key: "componentDidUpdate",
		value: function componentDidUpdate() {
			this.scrollToBottom();
		}
	}, {
		key: "handleKeyPress",
		value: function handleKeyPress(e) {

			if (e.key == 'Enter') {
				e.preventDefault();
				this.postuserchatdata(e);
			}
		}
	}, {
		key: "postuserchatdata",
		value: function postuserchatdata(e) {

			var postdata = { query: this.chatuserinput.current.innerText };

			var reactjsparent = this;

			// user data
			userdata = this.renderuserchatdata(this.chatuserinput.current.innerText);

			this.state.chatdata.push(userdata);
			this.setState({
				chatdata: this.state.chatdata
			});

			$.post(window.baseurlforchat + "index.php/admin/chatwindow/ajax/chatdata", postdata, function (data) {}).done(function (data) {

				console.log(data);
				// check if there is any reply from bot				
				if (data.botreply != '') {
					botdata = reactjsparent.renderbotchatdata(data.botreply);

					reactjsparent.state.chatdata.push(botdata);
					reactjsparent.setState({
						chatdata: reactjsparent.state.chatdata
					});
				} else if (data.error != '') {

					reactjsparent.state.boterror = data.error;
					reactjsparent.setState({
						boterror: reactjsparent.state.boterror
					});
				}
			}).fail(function (data) {
				console.log("error ");
				console.log(data);
				console.log(JSON.stringify(data));
			});

			this.chatuserinput.current.innerText = '';
			this.chatuserinput.current.replace(/(\r\n|\n|\r)/gm, "");
		}
	}, {
		key: "render",
		value: function render() {

			return React.createElement(
				"div",
				{ className: "chatwindowchatcontainer" },
				React.createElement(
					"div",
					{ className: "chatwindowchatdataheader" },
					" ",
					React.createElement(
						"a",
						{ href: "#", onClick: this.togglepopupwindow },
						" Chatwindow Bot "
					),
					" "
				),
				React.createElement(
					"div",
					{ ref: this.chatdatauserserver, className: "chatwindowchatdata" },
					this.state.chatdata
				),
				React.createElement(
					"div",
					{ className: "chatwindowboterror" },
					" ",
					this.state.boterror,
					" "
				),
				React.createElement(
					"div",
					{ className: "chatwindowchatdatabottom" },
					React.createElement(
						"div",
						{ contentEditable: "true", defaultValue: "", onKeyPress: this.handleKeyPress, ref: this.chatuserinput, id: "userchatdata" },
						" "
					),
					React.createElement(
						"div",
						{ className: "chatwindowbtncont" },
						" ",
						React.createElement(
							"button",
							{ type: "button", onClick: this.postuserchatdata, className: "btn btn-primary btn-sm" },
							"Send"
						),
						" "
					)
				)
			);
		}
	}]);

	return Chatbotwindow;
}(React.Component);

var Userchatdata = function (_React$Component3) {
	_inherits(Userchatdata, _React$Component3);

	function Userchatdata() {
		_classCallCheck(this, Userchatdata);

		return _possibleConstructorReturn(this, (Userchatdata.__proto__ || Object.getPrototypeOf(Userchatdata)).apply(this, arguments));
	}

	_createClass(Userchatdata, [{
		key: "render",
		value: function render() {
			return React.createElement(
				"div",
				{ className: "chatwindouwserchatdata" },
				React.createElement(
					"div",
					{ className: "chattitle" },
					" You "
				),
				React.createElement(
					"div",
					{ className: "chatborder" },
					this.props.userchat
				)
			);
		}
	}]);

	return Userchatdata;
}(React.Component);

var Rasaresponsedata = function (_React$Component4) {
	_inherits(Rasaresponsedata, _React$Component4);

	function Rasaresponsedata() {
		_classCallCheck(this, Rasaresponsedata);

		return _possibleConstructorReturn(this, (Rasaresponsedata.__proto__ || Object.getPrototypeOf(Rasaresponsedata)).apply(this, arguments));
	}

	_createClass(Rasaresponsedata, [{
		key: "render",
		value: function render() {
			return React.createElement(
				"div",
				{ className: "chatrasaresponsedata" },
				React.createElement(
					"div",
					{ className: "chattitle" },
					" Bot "
				),
				React.createElement(
					"div",
					{ className: "chatborder" },
					this.props.botreply
				)
			);
		}
	}]);

	return Rasaresponsedata;
}(React.Component);

ReactDOM.render(React.createElement(Chatboticon, null), document.getElementById('chatwindowchaticon'));
ReactDOM.render(React.createElement(Chatbotwindow, null), document.getElementById('chatwindowchat'));