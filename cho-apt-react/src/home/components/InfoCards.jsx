import React from 'react';
import { MdCheck } from 'react-icons/md';
import { CONTACT_INFO } from '../../data/constants';

export default function InfoCards() {
  return (
    <section id="information" className="info-section">
      <div className="container">
        <h2>Important Information</h2>
        <p className="info-subheader">
          Bridging Care through Digital Innovation: Empowering Communities with Accessible, Quality Healthcare Services
        </p>
        <div className="info-grid">
          {/* Before You Book */}
          <div className="info-card">
            <div className="info-card-header">
              <h3>Before You Book</h3>
            </div>
            <div className="info-card-before-item">
              <MdCheck size={20} />
              <span>Have your personal information ready</span>
            </div>
            <div className="info-card-before-item">
              <MdCheck size={20} />
              <span>Bring valid ID on appointment day</span>
            </div>
            <div className="info-card-before-item">
              <MdCheck size={20} />
              <span>Arrive 15 minutes before scheduled time</span>
            </div>
            <div className="info-card-before-item">
              <MdCheck size={20} />
              <span>Bring any relevant medical documents</span>
            </div>
          </div>

          {/* Need Help? */}
          <div className="info-card">
            <div className="info-card-header">
              <h3>Need Help?</h3>
            </div>
            <div className="info-card-content">
              <p>
                Our support team is available from {CONTACT_INFO.hours} to assist with your booking questions.
              </p>
              <div className="info-card-divider">
                <p><span>Call us:</span> {CONTACT_INFO.phone}</p>
                <p><span>Email:</span> {CONTACT_INFO.email}</p>
                <p><span>Visit:</span> {CONTACT_INFO.office}</p>
                <p><span>Location:</span> {CONTACT_INFO.address}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}