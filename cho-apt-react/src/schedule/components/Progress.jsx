import React from 'react';
import { STEPS } from '../../data/constants';

/**
 * Progress Component
 * Displays the current step in a multi-step booking flow
 * 
 * Props:
 *  - currentStep: Current step number (1, 2, 3, etc.)
 */
function Progress({ currentStep = 1 }) {
  return (
    <div className="progress-steps">
      {STEPS.map((step, idx) => (
        <React.Fragment key={step.number}>
          <div
            className={`progress-step ${
              currentStep >= step.number ? 'active' : ''
            }`}
          >
            <div className="step-circle">{step.number}</div>
            <span className="step-label">{step.label}</span>
          </div>
          {idx < STEPS.length - 1 && (
            <div
              className={`progress-divider ${
                currentStep > step.number ? 'active' : ''
              }`}
            />
          )}
        </React.Fragment>
      ))}
    </div>
  );
}

export default Progress;