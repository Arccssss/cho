import React from 'react';
import { MdCheck, MdErrorOutline } from 'react-icons/md';
import './ConfirmationSummary.css';

/**
 * ConfirmationSummary Component
 * Displays a summary of the confirmed appointment with all details
 * Can be used as a standalone confirmation screen
 */
export default function Confirmation({ 
  appointmentData,
  onClose,
  onPrint,
  showButtons = true
}) {
  if (!appointmentData) {
    return (
      <div className="confirmation-summary error">
        <div className="confirmation-icon error-icon">
          <MdErrorOutline size={64} />
        </div>
        <h2>No Appointment Data</h2>
        <p>Unable to display confirmation. Please try booking again.</p>
        {showButtons && (
          <button className="schedule-btn-primary" onClick={onClose}>
            Go Back
          </button>
        )}
      </div>
    );
  }

  const getServiceName = (serviceId) => {
    const services = {
      'medical': 'Medical Consultation',
      'animal-bite': 'Animal Bite Treatment',
      'dental': 'Dental Care'
    };
    return services[serviceId] || serviceId;
  };

  const formatDate = (dateString) => {
    const date = new Date(dateString + 'T00:00:00');
    return date.toLocaleDateString('en-US', { 
      weekday: 'long', 
      year: 'numeric', 
      month: 'long', 
      day: 'numeric' 
    });
  };

  return (
    <div className="confirmation-summary success">
      {/* Success Icon */}
      <div className="confirmation-icon success-icon">
        <MdCheck size={64} />
      </div>

      {/* Success Message */}
      <div className="confirmation-header">
        <h2>Appointment Confirmed!</h2>
        <p className="confirmation-text">
          Your appointment has been successfully scheduled. Check your email for a confirmation message with all the details.
        </p>
      </div>

      {/* Confirmation Details */}
      <div className="confirmation-details">
        <div className="details-card">
          <div className="details-section">
            <h3 className="section-title">Appointment Details</h3>
            
            <div className="detail-item">
              <span className="detail-label">Service:</span>
              <span className="detail-value">
                {getServiceName(appointmentData.service)}
              </span>
            </div>

            <div className="detail-item">
              <span className="detail-label">Date:</span>
              <span className="detail-value">
                {formatDate(appointmentData.appointmentDate)}
              </span>
            </div>

            <div className="detail-item">
              <span className="detail-label">Time:</span>
              <span className="detail-value">
                {appointmentData.appointmentTime}
              </span>
            </div>

            <div className="detail-item">
              <span className="detail-label">Confirmation #:</span>
              <span className="detail-value confirmation-number">
                #{Math.random().toString(36).substring(2, 11).toUpperCase()}
              </span>
            </div>
          </div>

          <div className="divider"></div>

          <div className="details-section">
            <h3 className="section-title">Patient Information</h3>
            
            <div className="detail-item">
              <span className="detail-label">Name:</span>
              <span className="detail-value">
                {appointmentData.firstName} {appointmentData.lastName}
              </span>
            </div>

            <div className="detail-item">
              <span className="detail-label">Contact Number:</span>
              <span className="detail-value">
                {appointmentData.contactNumber}
              </span>
            </div>

            <div className="detail-item">
              <span className="detail-label">Email:</span>
              <span className="detail-value email-value">
                {appointmentData.email}
              </span>
            </div>
          </div>
        </div>
      </div>

      {/* Important Information Box */}
      <div className="info-section">
        <h3>Important Information</h3>
        <ul className="info-list">
          <li>Arrive 15 minutes before your scheduled appointment time</li>
          <li>Bring a valid ID and any relevant medical documents</li>
          <li>If you need to reschedule, contact us at least 24 hours in advance</li>
          <li>A confirmation email has been sent to <strong>{appointmentData.email}</strong></li>
        </ul>
      </div>

      {/* Contact Information */}
      <div className="contact-section">
        <h3>Need Help?</h3>
        <p>If you have any questions or need to reschedule:</p>
        <div className="contact-details">
          <div className="contact-item">
            <span className="contact-label">Phone:</span>
            <span className="contact-value">(034) 431-36-73</span>
          </div>
          <div className="contact-item">
            <span className="contact-label">Email:</span>
            <span className="contact-value">admin_bcho@gov.ph</span>
          </div>
          <div className="contact-item">
            <span className="contact-label">Hours:</span>
            <span className="contact-value">Monday - Friday, 8:00 AM - 5:00 PM</span>
          </div>
        </div>
      </div>

      {/* Action Buttons */}
      {showButtons && (
        <div className="confirmation-actions">
          {onPrint && (
            <button className="btn-secondary" onClick={onPrint}>
              Print Confirmation
            </button>
          )}
          <button className="schedule-btn-primary" onClick={onClose}>
            Back to Home
          </button>
        </div>
      )}
    </div>
  );
}