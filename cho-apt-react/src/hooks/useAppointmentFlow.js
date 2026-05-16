import { useState } from 'react';

/**
 * useAppointmentFlow Hook
 * Manages the multi-step appointment scheduling flow with validation
 */
export function useAppointmentFlow() {
  const [currentStep, setCurrentStep] = useState(1);
  const [selectedService, setSelectedService] = useState(null);
  const [selectedTime, setSelectedTime] = useState(null);

  const goToStep = (step) => {
    setCurrentStep(step);
  };

  const nextStep = () => {
    setCurrentStep((prev) => Math.min(prev + 1, 3));
  };

  const previousStep = () => {
    setCurrentStep((prev) => Math.max(prev - 1, 1));
  };

  const validateStep1 = () => {
    if (!selectedService) {
      alert('Please select a service');
      return false;
    }
    return true;
  };

  const validateStep2 = (formData) => {
    const requiredFields = ['firstName', 'lastName', 'contactNumber', 'email'];
    const missing = requiredFields.filter((field) => !formData[field]);

    if (missing.length > 0) {
      alert('Please fill in all required fields marked with *');
      return false;
    }

    // Email validation
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(formData.email)) {
      alert('Please enter a valid email address');
      return false;
    }

    return true;
  };

  const validateStep3 = (selectedDate) => {
    if (!selectedDate || !selectedTime) {
      alert('Please select both date and time');
      return false;
    }
    return true;
  };

  const handleNextStep = (validationFn) => {
    if (validationFn && !validationFn()) {
      return false;
    }
    nextStep();
    return true;
  };

  return {
    currentStep,
    selectedService,
    setSelectedService,
    selectedTime,
    setSelectedTime,
    goToStep,
    nextStep,
    previousStep,
    handleNextStep,
    validateStep1,
    validateStep2,
    validateStep3,
  };
}