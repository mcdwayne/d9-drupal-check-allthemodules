import Page from './components/Page/Page';
import { Provider } from 'react-redux';
import { applyMiddleware, combineReducers, createStore } from 'redux';
import { reducer as api, readEndpoint } from 'redux-json-api';
import { setEndpointHost, setEndpointPath, setHeaders } from 'redux-json-api';
import thunk from 'redux-thunk';

var page = document.getElementById('reactjs-page');
var page_id = page.getAttribute('page');

const reducer = combineReducers({
  api
});

const store = createStore(reducer, {can_design: true}, applyMiddleware(thunk));
store.dispatch(setHeaders({
  Authorization: 'Basic YWRtaW46YWRtaW4=',
}));
store.dispatch(setEndpointHost('/'));
store.dispatch(setEndpointPath('jsonapi'));
store.dispatch(readEndpoint('page/page/' + page_id));

ReactDOM.render(
  <Provider store={store}><Page /></Provider>,
  page
);
