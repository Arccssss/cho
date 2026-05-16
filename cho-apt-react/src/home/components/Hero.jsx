import React from 'react';

export default function Hero({ onScheduleClick }) {
  return (
    <section className="hero">
      <div className="container">
        <h1>Bacolod City Health Office</h1>
        <p className="hero-subheader">
          Providing quality healthcare services to the community
        </p>
        <button 
          className="btn-primary" 
          onClick={onScheduleClick}
        >
          Schedule Your Appointment
        </button>
      </div>
    </section>
  );
}