import React from 'react';
import NoPicture from '../icons/no-picture';
import './UserPicture.css';

export default (props) => {
  const thumbnail = props.thumbnail
      ? <img alt={window.Drupal.t('User avatar')} src={props.thumbnail} />
      : <NoPicture />;

  return (
      <div className="rc_avatar">
        <div className="rc_avatar__image-wrapper">
          {thumbnail}
        </div>
      </div>
  );
};