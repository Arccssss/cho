import React from 'react';
import { MdCheck } from 'react-icons/md';
import { SERVICES, STEPS } from '../../data/constants';

export default function ServiceSelection({ selectedService, onSelectService }) {
  return (
    <div className="step-content">
      <h2>{STEPS[0].label}</h2>
      <p className="step-subtitle">Choose the type of health service you wish to book an appointment for.</p>

      <div className="services-grid">
        {SERVICES.map((service) => (
          <button
            key={service.id}
            className={`service-card ${
              selectedService === service.id ? 'selected' : ''
            }`}
            onClick={() => onSelectService(service.id)}
          >
            {selectedService === service.id && (
              <div className="service-checkmark">
                <MdCheck size={16} />
              </div>
            )}
            <div className="service-icon">{service.icon}</div>
            <h3>{service.title}</h3>
            <p>{service.description}</p>
          </button>
        ))}
      </div>

      <div className="info-box">
        <p><strong>Need help choosing?</strong> Our staff can help you select the appropriate service. Visit our clinic or call us for assistance.</p>
      </div>
    </div>
  );
}