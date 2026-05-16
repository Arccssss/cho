import React from 'react';
import { useCalendar } from '../hooks/useCalendar';
import { TIME_SLOTS, MONTH_NAMES, DAY_NAMES } from '../data/constants';
import './DateTimeSelector.css';

export default function DateTimeSelector({ 
  patientData, 
  selectedService,
  onConfirm,
  onBack 
}) {
  const calendar = useCalendar();
  const [selectedTime, setSelectedTime] = React.useState(null);

  const handleTimeSelect = (time) => {
    setSelectedTime(time);
  };

  const handleConfirm = () => {
    if (!calendar.selectedDate || !selectedTime) {
      alert('Please select both date and time');
      return;
    }

    onConfirm({
      appointmentDate: calendar.selectedDate.toISOString().split('T')[0],
      appointmentTime: selectedTime
    });
  };

  return (
    <div className="date-time-selector">
      <h2>Select Date & Time</h2>
      <p className="step-subtitle">Choose your preferred appointment date and time.</p>

      <div className="selector-grid">
        {/* Calendar Section */}
        <div className="calendar-section">
          <div className="calendar-card">
            {/* Month Header */}
            <div className="calendar-header">
              <button className="nav-button" onClick={calendar.handlePreviousMonth}>
                ◀
              </button>
              <h3 className="current-month">
                {MONTH_NAMES[calendar.currentDate.getMonth()]} {calendar.currentDate.getFullYear()}
              </h3>
              <button className="nav-button" onClick={calendar.handleNextMonth}>
                ▶
              </button>
            </div>

            {/* Day Names */}
            <div className="calendar-weekdays">
              {DAY_NAMES.map(day => (
                <div key={day} className="weekday-label">{day}</div>
              ))}
            </div>

            {/* Calendar Days */}
            <div className="calendar-days">
              {calendar.buildCalendarDays().map((day, index) => (
                <button
                  key={index}
                  className={`calendar-day ${calendar.isDateSelected(day) ? 'selected' : ''} ${calendar.isDatePast(day) ? 'disabled' : ''} ${!day ? 'empty' : ''}`}
                  onClick={() => day && !calendar.isDatePast(day) && calendar.handleDateSelect(day)}
                  disabled={!day || calendar.isDatePast(day)}
                >
                  {day}
                </button>
              ))}
            </div>

            {/* Legend */}
            <div className="calendar-legend">
              <div className="legend-item">
                <div className="legend-dot available"></div>
                <span>Available</span>
              </div>
              <div className="legend-item">
                <div className="legend-dot disabled"></div>
                <span>Past Date</span>
              </div>
              <div className="legend-item">
                <div className="legend-dot selected"></div>
                <span>Selected</span>
              </div>
            </div>
          </div>
        </div>

        {/* Time Slots Section */}
        <div className="timeslots-section">
          {calendar.selectedDate ? (
            <div className="timeslots-card">
              <div className="timeslots-header">
                <h3>Available Times</h3>
                <p className="selected-date">
                  {calendar.selectedDate.toLocaleDateString('en-US', { 
                    weekday: 'long', 
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric' 
                  })}
                </p>
              </div>

              <div className="timeslots-grid">
                {TIME_SLOTS.map((slot, index) => (
                  <button
                    key={index}
                    className={`timeslot-button ${slot.available ? '' : 'unavailable'} ${selectedTime === slot.time ? 'selected' : ''}`}
                    onClick={() => slot.available && handleTimeSelect(slot.time)}
                    disabled={!slot.available}
                  >
                    <span className="time">{slot.time}</span>
                    {!slot.available && <span className="unavailable-badge">Booked</span>}
                  </button>
                ))}
              </div>

              {selectedTime && (
                <div className="selection-summary">
                  <h4>Your Selection</h4>
                  <div className="summary-item">
                    <span className="label">Date:</span>
                    <span className="value">{calendar.selectedDate.toLocaleDateString()}</span>
                  </div>
                  <div className="summary-item">
                    <span className="label">Time:</span>
                    <span className="value">{selectedTime}</span>
                  </div>
                </div>
              )}
            </div>
          ) : (
            <div className="timeslots-card empty">
              <div className="empty-state">
                <div className="empty-icon">📅</div>
                <p>Select a date from the calendar to view available time slots.</p>
              </div>
            </div>
          )}
        </div>
      </div>

      {/* Info Box */}
      <div className="info-box">
        <p><strong>Note:</strong> Time slots shown are subject to availability. Once you confirm your appointment, you'll receive a confirmation email.</p>
      </div>

      {/* Action Buttons */}
      <div className="selector-actions">
        <button className="btn-secondary" onClick={onBack}>
          Back
        </button>
        <button 
          className="schedule-btn-primary" 
          onClick={handleConfirm}
          disabled={!calendar.selectedDate || !selectedTime}
        >
          Confirm Appointment
        </button>
      </div>
    </div>
  );
}