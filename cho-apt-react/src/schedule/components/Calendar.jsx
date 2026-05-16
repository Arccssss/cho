import React from 'react';

export default function Calendar({
  currentDate,
  selectedDate,
  onPreviousMonth,
  onNextMonth,
  onDateSelect,
  isDateSelected,
  isDatePast,
  buildCalendarDays,
  monthNames,
  dayNames,
  stepLabel,
  stepSubtitle,
  infoBoxText
}) {
  return (
    <div className="calendar-wrapper">
      {stepLabel && (
        <div className="step-content">
          <h2>{stepLabel}</h2>
          {stepSubtitle && <p className="step-subtitle">{stepSubtitle}</p>}
        </div>
      )}

      <div className="calendar-step-card">
        <div className="calendar-header">
          <button className="nav-button" onClick={onPreviousMonth}>
            ◀
          </button>
          <h3 className="current-month">
            {monthNames[currentDate.getMonth()]} {currentDate.getFullYear()}
          </h3>
          <button className="nav-button" onClick={onNextMonth}>
            ▶
          </button>
        </div>

        <div className="calendar-weekdays">
          {dayNames.map(day => (
            <div key={day} className="weekday-label">{day}</div>
          ))}
        </div>

        <div className="calendar-days">
          {buildCalendarDays().map((day, index) => (
            <button
              key={index}
              className={`calendar-day ${isDateSelected(day) ? 'selected' : ''} ${isDatePast(day) ? 'disabled' : ''} ${!day ? 'empty' : ''}`}
              onClick={() => day && !isDatePast(day) && onDateSelect(day)}
              disabled={!day || isDatePast(day)}
            >
              {day}
            </button>
          ))}
        </div>

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

      {infoBoxText && (
        <div className="info-box">
          <p>{infoBoxText}</p>
        </div>
      )}
    </div>
  );
}