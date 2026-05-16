import React from 'react';
import { MdEdit } from 'react-icons/md';

export default function BookingInfo({ selectedDate, formData, onEdit, onConfirm, isComplete }) {
  return (
    <div className="booking-details-section">
      <div className="booking-details-card">
        {/* Date Section */}
        <div className="booking-details-section-header">
          <h3>Date</h3>
        </div>
        <div className="booking-date-display">
          <div className="date-header-row">
            <p className="booking-date-value">
              {selectedDate ? selectedDate.toLocaleDateString('en-US', { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
              }) : 'Not selected'}
            </p>
            <span className="slots-badge slots-badge-header">Slots Available</span>
          </div>
          <div className="booking-time-header">
            <p className="booking-time-value">Whole Day 8AM - 5PM</p>
          </div>
        </div>

        {/* Information Section */}
        <div className="booking-details-section-header booking-details-section-header-mt">
          <div className="section-title-with-icon">
            <h3>Information</h3>
            <button className="edit-btn" onClick={onEdit} title="Edit information">
              <MdEdit size={18} />
            </button>
          </div>
        </div>

        <div className="booking-info-display">
          <div className="info-row">
            <span className="info-label">Name</span>
            <span className="info-value">
              {formData.firstName && formData.lastName 
                ? `${formData.firstName} ${formData.lastName}` 
                : 'Not provided'}
            </span>
          </div>

          <div className="info-row">
            <span className="info-label">Birthday</span>
            <span className="info-value">
              {formData.birthMonth && formData.birthDay && formData.birthYear
                ? `${formData.birthMonth}/${formData.birthDay}/${formData.birthYear}`
                : 'Not provided'}
            </span>
          </div>

          <div className="info-row">
            <span className="info-label">Purpose</span>
            <span className="info-value">
              {formData.purpose || 'Not provided'}
            </span>
          </div>

          <div className="info-row">
            <span className="info-label">Contact Number</span>
            <span className="info-value">
              {formData.contactNumber || 'Not provided'}
            </span>
          </div>
        </div>

        {/* Confirm Button */}
        <div className="booking-actions">
          <button 
            className="schedule-btn-primary btn-full-width"
            onClick={onConfirm}
            disabled={!selectedDate || !formData.firstName || !formData.lastName || !formData.contactNumber}
          >
            Confirm Schedule
          </button>
        </div>
      </div>
    </div>
  );
}