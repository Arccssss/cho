import React from 'react';

export default function CTASection({ onScheduleClick }) {
  return (
    <section className="cta-section">
      <div className="container">
        <h2>Ready to get started?</h2>
        <p>
          Join thousands of patients enjoying seamless healthcare management with Samotial Health Portal. Access your health records anytime, anywhere.
        </p>
        <div className="cta-buttons">
          <button 
            className="btn-primary" 
            onClick={onScheduleClick}
          >
            Schedule Your Appointment
          </button>
        </div>
      </div>
    </section>
  );
}