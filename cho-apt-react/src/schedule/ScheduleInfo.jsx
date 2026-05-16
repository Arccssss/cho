import React, { useState, useCallback } from 'react';
import Navigation from '../components/Navigation';
import ServiceSelection from './components/ServiceSelection';
import PatientForm from './components/PatientForm';
import Calendar from './components/Calendar';
import BookingInfo from './components/BookingInfo';
import Progress from './components/Progress';
import { SERVICES, MONTH_NAMES, DAY_NAMES, STEPS, APPOINTMENT_TIME } from '../data/constants';
import { validateField, validateForm, isFormComplete } from '../data/validationRules';
import './ScheduleInfo.css';

export default function ScheduleInfo({ onBack, onNext }) {
  const [currentStep, setCurrentStep] = useState(1);
  const [selectedService, setSelectedService] = useState(null);
  const [selectedDate, setSelectedDate] = useState(null);
  const [currentDate, setCurrentDate] = useState(new Date());
  
  const [formData, setFormData] = useState({
    firstName: '',
    lastName: '',
    middleName: '',
    email: '',
    contactNumber: '',
    birthMonth: '',
    birthDay: '',
    birthYear: '',
    sex: '',
    civilStatus: '',
    barangay: '',
    philhealthMember: '',
    philhealthNumber: '',
    primaryCareBenefitMember: '',
    philhealthCategory: '',
    notes: '',
    purpose: ''
  });

  const [touched, setTouched] = useState(new Set());
  const [errors, setErrors] = useState({});

  const months = MONTH_NAMES;
  const days = Array.from({ length: 31 }, (_, i) => i + 1);
  const years = Array.from({ length: 100 }, (_, i) => new Date().getFullYear() - i);

  const handleSelectService = (serviceId) => {
    setSelectedService(serviceId);
    const selectedServiceObj = SERVICES.find(s => s.id === serviceId);
    if (selectedServiceObj) {
      setFormData(prev => ({
        ...prev,
        purpose: selectedServiceObj.title
      }));
    }
  };

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
  };

  const handleBlur = useCallback((e) => {
    const { name } = e.target;
    const value = e.target.value;

    setTouched(prev => new Set([...prev, name]));

    const { isValid, error } = validateField(name, value);
    
    if (!isValid) {
      setErrors(prev => ({ ...prev, [name]: error }));
    } else {
      setErrors(prev => {
        const newErrors = { ...prev };
        delete newErrors[name];
        return newErrors;
      });
    }
  }, []);

  const getFieldError = (fieldName) => {
    return touched.has(fieldName) ? errors[fieldName] : undefined;
  };

  const handleDateSelect = (day) => {
    if (!day || isDatePast(day)) return;
    
    const newDate = new Date(currentDate.getFullYear(), currentDate.getMonth(), day);
    setSelectedDate(newDate);
  };

  const isDatePast = (day) => {
    if (!day) return false;
    const testDate = new Date(currentDate.getFullYear(), currentDate.getMonth(), day);
    return testDate < new Date();
  };

  const isDateSelected = (day) => {
    if (!day || !selectedDate) return false;
    return (
      day === selectedDate.getDate() &&
      currentDate.getMonth() === selectedDate.getMonth() &&
      currentDate.getFullYear() === selectedDate.getFullYear()
    );
  };

  const buildCalendarDays = () => {
    const year = currentDate.getFullYear();
    const month = currentDate.getMonth();
    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();

    const days = [];
    for (let i = 0; i < firstDay; i++) {
      days.push(null);
    }
    for (let i = 1; i <= daysInMonth; i++) {
      days.push(i);
    }
    return days;
  };

  const handlePreviousMonth = () => {
    setCurrentDate(new Date(currentDate.getFullYear(), currentDate.getMonth() - 1));
  };

  const handleNextMonth = () => {
    setCurrentDate(new Date(currentDate.getFullYear(), currentDate.getMonth() + 1));
  };

  const validateAndProceedToCalendar = () => {
    const requiredFields = [
      'firstName',
      'lastName',
      'email',
      'contactNumber',
      'birthMonth',
      'birthDay',
      'birthYear',
      'sex'
    ];

    const validation = validateForm(formData, requiredFields);

    if (!validation.isValid) {
      setErrors(validation.errors);
      setTouched(new Set(requiredFields));
      return;
    }

    setCurrentStep(3);
  };

  const handleNextStep = () => {
    if (currentStep === 1) {
      if (selectedService) {
        setCurrentStep(2);
      }
    } else if (currentStep === 2) {
      validateAndProceedToCalendar();
    }
  };

  const handlePreviousStep = () => {
    setErrors({});
    setTouched(new Set());
    setCurrentStep(currentStep - 1);
  };

  const handleEditInfo = () => {
    setErrors({});
    setTouched(new Set());
    setCurrentStep(2);
  };

  const handleConfirmAppointment = () => {
    if (!selectedDate) {
      alert('Please select a date for your appointment');
      return;
    }

    const appointmentData = {
      ...formData,
      service: selectedService,
      appointmentDate: selectedDate.toISOString().split('T')[0],
      appointmentTime: APPOINTMENT_TIME,
      confirmedAt: new Date().toISOString()
    };

    console.log('Appointment confirmed:', appointmentData);
    
    alert(`Appointment confirmed for ${selectedDate.toLocaleDateString()} (${APPOINTMENT_TIME})`);
    
    setFormData({
      firstName: '',
      lastName: '',
      middleName: '',
      email: '',
      contactNumber: '',
      birthMonth: '',
      birthDay: '',
      birthYear: '',
      sex: '',
      civilStatus: '',
      barangay: '',
      philhealthMember: '',
      philhealthNumber: '',
      primaryCareBenefitMember: '',
      philhealthCategory: '',
      notes: '',
      purpose: ''
    });
    setSelectedService(null);
    setSelectedDate(null);
    setCurrentDate(new Date());
    setErrors({});
    setTouched(new Set());
    setCurrentStep(1);
    
    onNext();
  };

  return (
    <div className="schedule-container">
      <Navigation title="Schedule Appointment" onBack={onBack} />

      <div className="schedule-main">
        <div className="schedule-container-inner">
          <Progress currentStep={currentStep} />

          <div className="schedule-content">
            {currentStep === 1 && (
              <ServiceSelection
                selectedService={selectedService}
                onSelectService={handleSelectService}
              />
            )}

            {currentStep === 2 && (
              <PatientForm
                formData={formData}
                onInputChange={handleInputChange}
                onBlur={handleBlur}
                getFieldError={getFieldError}
                months={months}
                days={days}
                years={years}
              />
            )}

            {currentStep === 3 && (
              <>
                <div className="step-content">
                  <h2>{STEPS[2].label}</h2>
                  <p className="step-subtitle">Choose your preferred appointment date and confirm your booking information.</p>
                </div>

                <div className="calendar-step-grid">
                  <Calendar
                    currentDate={currentDate}
                    selectedDate={selectedDate}
                    onPreviousMonth={handlePreviousMonth}
                    onNextMonth={handleNextMonth}
                    onDateSelect={handleDateSelect}
                    isDateSelected={isDateSelected}
                    isDatePast={isDatePast}
                    buildCalendarDays={buildCalendarDays}
                    monthNames={MONTH_NAMES}
                    dayNames={DAY_NAMES}
                  />

                  <BookingInfo
                    selectedDate={selectedDate}
                    formData={formData}
                    onEdit={handleEditInfo}
                    onConfirm={handleConfirmAppointment}
                    isComplete={selectedDate && isFormComplete(formData)}
                  />
                </div>

                <div className="info-box">
                  <p><strong>Note:</strong> Appointments are scheduled for whole day (8AM - 5PM). Once you confirm, you'll receive a confirmation email with all booking details.</p>
                </div>
              </>
            )}
          </div>

          <div className="step-actions">
            <button 
              className="btn-secondary"
              onClick={handlePreviousStep}
              disabled={currentStep === 1}
            >
              Back
            </button>
            
            <button 
              className={`schedule-btn-primary ${currentStep === 3 ? 'hidden' : ''}`}
              onClick={handleNextStep}
              disabled={currentStep === 3}
            >
              {currentStep === 1 ? 'Next' : currentStep === 2 ? 'Continue to Calendar' : ''}
            </button>
          </div>  
        </div>
      </div>
    </div>
  );
}