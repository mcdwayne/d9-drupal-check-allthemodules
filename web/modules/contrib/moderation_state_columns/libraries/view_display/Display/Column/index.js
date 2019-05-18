import React from 'react';

const Column = props => {
  const { entitiesInState = [], stateId, stateLabel } = props;
  return (
    <div className={`moderation-state-column moderation-state-column--${stateId}`}>
      <h2>{stateLabel}</h2>
      <div className="moderation-state-column--content">
        {entitiesInState.map(entity => (
          <div
            className="moderation-state-column--item"
            dangerouslySetInnerHTML={{ __html: entity }}
            key={entity}
          />
        ))}
      </div>
    </div>
  );
};

export default Column;
