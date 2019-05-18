import React from 'react';
import ReplyList from './ReplyList';
import UserPicture from "./UserPicture";

export default (props) => (
    <div className='rc_comment rc_comment--deleted'>
        <div className="rc_comment-container">
            <UserPicture />
            <div className="rc_body">
                <div className="rc_comment-details">
                    {window.Drupal.t('This comment has been deleted.')}
                </div>
            </div>
        </div>
        { props.replies &&
        <ReplyList
            {...props}
            replyTo={props.user}
        />
        }
    </div>
);
