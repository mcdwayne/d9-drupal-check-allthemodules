import React from 'react';
import Comment from './Comment';

export default ({ currentUser, settings, level, replies, replyTo, saveEdit, postReply, vote, toggleMenu, openMenu, flagComment, publishComment, unpublishComment, deleteComment }) => {
    // stop nesting on the 4th level (zero indexed)
    const containerClasses = (level >= 3) ? 'rc_replies rc_reply--max-level' : 'rc_replies';

    return (
        <div className={containerClasses}>
            {replies.map((reply, i) => {
                return <Comment
                    key={i}
                    level={level + 1}
                    currentUser={currentUser}
                    settings={settings}
                    replyTo={replyTo}
                    saveEdit={saveEdit}
                    postReply={postReply}
                    vote={vote}
                    toggleMenu={toggleMenu}
                    openMenu={openMenu}
                    flagComment={flagComment}
                    publishComment={publishComment}
                    unpublishComment={unpublishComment}
                    deleteComment={deleteComment}
                    {...reply}/>
            })}
        </div>
    )
};
