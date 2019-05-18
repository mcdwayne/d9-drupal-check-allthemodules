import React, { Component } from 'react';
import ReactDOM from 'react-dom';
import Constants from '../utils/constants';
import Caret from '../icons/caret-down';

class CommentMenu extends Component {

    documentClick = (e) => {
        const menu = ReactDOM.findDOMNode(this.refs.menu);
        const toggle = ReactDOM.findDOMNode(this.refs.toggle);

        if (menu && toggle && !menu.contains(e.target) && this.props.id === this.props.openMenu && !toggle.contains(e.target)) {
            this.props.toggleMenu(this.props.id);
        }
    };

    componentWillMount() {
        document.addEventListener('click', this.documentClick, false);
    }

    componentWillUnmount() {
        document.removeEventListener('click', this.documentClick, false);
    }

    render() {
        const {user, currentUser, id, openMenu, closeMenu, toggleMenu, flagComment, deleteComment, publishComment, unpublishComment, status, published} = this.props;
        const userCanDeleteComment = currentUser && !currentUser.isAnon && currentUser.hasPermission('edit own comments') && (user.id === currentUser.id);
        const userCanAdministerComments = currentUser && currentUser.hasPermission('administer comments');
        const userCanFlagComment = currentUser && currentUser.hasPermission('restful put comment');

        const menuItems = [];
        if (userCanAdministerComments && (published === '0')) menuItems.push(<li onClick={() => {publishComment(id); closeMenu();}}>{window.Drupal.t('Publish')}</li>);
        if (userCanAdministerComments && (published === '1')) menuItems.push(<li onClick={() => {unpublishComment(id); closeMenu();}}>{window.Drupal.t('Unpublish')}</li>);
        if (userCanAdministerComments || userCanDeleteComment) menuItems.push(<li onClick={() => {deleteComment(id); closeMenu();}}>{window.Drupal.t('Delete')}</li>);
        if (userCanFlagComment) menuItems.push(status !== Constants.flagged ? <li onClick={() => {flagComment(id); closeMenu();}}>{window.Drupal.t('Flag as inappropriate')}</li> : <li>{window.Drupal.t('Flagged')}</li>);

        return menuItems.length > 0 ? (
            <div className="rc_comment-menu-wrapper">
                <div ref="toggle"
                     className="rc_comment-menu-toggle"
                     onClick={(e) => { e.preventDefault(); toggleMenu(id);}}><Caret /></div>
                <ul ref="menu"
                    className={(id === openMenu) ? 'rc_comment-menu rc_comment-menu--active' : 'rc_comment-menu'} >
                    { menuItems }
                </ul>
            </div>
        ) : null;
    }

}

export default CommentMenu;
