import React from 'react';
import Column from './Column';

const Display = props => {
  const { entities, states } = props;
  const stateIds = Object.keys(states);
  return (
    stateIds.map(stateId => (
      <Column
        entitiesInState={entities[stateId]}
        key={stateId}
        stateId={stateId}
        stateLabel={states[stateId]}
      />
    ))
  );
};

export default Display;
