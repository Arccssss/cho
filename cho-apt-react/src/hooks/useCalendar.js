import { useState } from 'react';

/**
 * useCalendar Hook
 * Handles all calendar logic: date selection, month navigation, date validation
 */
export function useCalendar() {
  const [currentDate, setCurrentDate] = useState(new Date());
  const [selectedDate, setSelectedDate] = useState(null);

  const getDaysInMonth = (date) => {
    return new Date(date.getFullYear(), date.getMonth() + 1, 0).getDate();
  };

  const getFirstDayOfMonth = (date) => {
    return new Date(date.getFullYear(), date.getMonth(), 1).getDay();
  };

  const handlePreviousMonth = () => {
    setCurrentDate(new Date(currentDate.getFullYear(), currentDate.getMonth() - 1));
  };

  const handleNextMonth = () => {
    setCurrentDate(new Date(currentDate.getFullYear(), currentDate.getMonth() + 1));
  };

  const handleDateSelect = (day) => {
    const selected = new Date(currentDate.getFullYear(), currentDate.getMonth(), day);
    setSelectedDate(selected);
  };

  const isDateSelected = (day) => {
    if (!selectedDate || !day) return false;
    return (
      selectedDate.getDate() === day &&
      selectedDate.getMonth() === currentDate.getMonth() &&
      selectedDate.getFullYear() === currentDate.getFullYear()
    );
  };

  const isDatePast = (day) => {
    if (!day) return false;
    const checkDate = new Date(currentDate.getFullYear(), currentDate.getMonth(), day);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    return checkDate < today;
  };

  const buildCalendarDays = () => {
    const daysInMonth = getDaysInMonth(currentDate);
    const firstDay = getFirstDayOfMonth(currentDate);
    const calendarDays = [];

    for (let i = 0; i < firstDay; i++) {
      calendarDays.push(null);
    }

    for (let i = 1; i <= daysInMonth; i++) {
      calendarDays.push(i);
    }

    return calendarDays;
  };

  return {
    currentDate,
    selectedDate,
    setSelectedDate,
    handlePreviousMonth,
    handleNextMonth,
    handleDateSelect,
    isDateSelected,
    isDatePast,
    buildCalendarDays,
  };
}