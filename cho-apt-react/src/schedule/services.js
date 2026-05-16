// ========================================
// SCHEDULE FEATURE BUSINESS LOGIC
// ========================================

import { SERVICES } from '../data/constants';

/**
 * Service Functions
 */

export const getServiceById = (serviceId) => {
  return SERVICES.find(service => service.id === serviceId);
};

/**
 * Form Validation Functions
 */

export const validateServiceSelection = (selectedService) => {
  if (!selectedService) {
    return { valid: false, message: 'Please select a service' };
  }
  return { valid: true };
};

export const validatePatientForm = (formData) => {
  const requiredFields = ['firstName', 'lastName', 'contactNumber', 'email'];
  const missingFields = requiredFields.filter(field => !formData[field]);

  if (missingFields.length > 0) {
    return { 
      valid: false, 
      message: 'Please fill in all required fields marked with *',
      missingFields 
    };
  }

  // Email validation
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailRegex.test(formData.email)) {
    return { valid: false, message: 'Please enter a valid email address' };
  }

  // Phone validation (basic)
  if (formData.contactNumber.length < 7) {
    return { valid: false, message: 'Please enter a valid contact number' };
  }

  return { valid: true };
};

export const validateDateTimeSelection = (selectedDate, selectedTime) => {
  if (!selectedDate) {
    return { valid: false, message: 'Please select a date' };
  }
  if (!selectedTime) {
    return { valid: false, message: 'Please select a time' };
  }
  return { valid: true };
};

/**
 * Appointment Functions
 */

export const buildAppointmentData = (formData, selectedService, selectedDate, selectedTime) => {
  return {
    ...formData,
    service: selectedService,
    appointmentDate: selectedDate.toISOString().split('T')[0],
    appointmentTime: selectedTime,
    confirmedAt: new Date().toISOString()
  };
};

/**
 * Booking Info Functions
 */

export const formatPatientName = (formData) => {
  const { firstName, lastName } = formData;
  if (firstName && lastName) {
    return `${firstName} ${lastName}`;
  }
  return 'Not provided';
};

export const formatBirthday = (formData) => {
  const { birthMonth, birthDay, birthYear } = formData;
  if (birthMonth && birthDay && birthYear) {
    return `${birthMonth}/${birthDay}/${birthYear}`;
  }
  return 'Not provided';
};

export const formatContactNumber = (contactNumber) => {
  return contactNumber || 'Not provided';
};

export const formatPurpose = (purpose) => {
  return purpose || 'Not provided';
};

export const isBookingInfoComplete = (selectedDate, formData) => {
  return (
    selectedDate &&
    formData.firstName &&
    formData.lastName &&
    formData.contactNumber
  );
};

export const getBookingSummary = (selectedDate, formData, selectedService) => {
  const serviceTitle = getServiceById(selectedService)?.title || selectedService;
  return {
    date: selectedDate ? selectedDate.toLocaleDateString() : 'Not selected',
    time: 'Whole Day 8AM - 5PM',
    name: formatPatientName(formData),
    birthday: formatBirthday(formData),
    purpose: formatPurpose(formData.purpose),
    contactNumber: formatContactNumber(formData.contactNumber),
    service: serviceTitle
  };
};