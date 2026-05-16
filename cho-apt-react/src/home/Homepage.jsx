import React from 'react';
import Hero from './components/Hero';
import Features from './components/Features';
import InfoCards from './components/InfoCards';
import CTASection from './components/CTASection';
import './HomePage.css';

export default function HomePage({ onScheduleClick }) {
  return (
    <div className="homepage">
      <Hero onScheduleClick={onScheduleClick} />
      <Features />
      <InfoCards />
      <CTASection onScheduleClick={onScheduleClick} />
    </div>
  );
}