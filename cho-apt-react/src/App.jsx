import React from 'react';
import './App.css'
import Header from './components/Header';
import Footer from './components/Footer';
import HomePage from './home/HomePage';
import ScheduleInfo from './schedule/ScheduleInfo';

export default function HealthPortal() {
  const [currentPage, setCurrentPage] = React.useState('home');
  const [selectedService, setSelectedService] = React.useState(null);
  const [formData, setFormData] = React.useState({
    firstName: '',
    lastName: '',
    middleName: '',
    suffix: '',
    birthMonth: '',
    birthDay: '',
    birthYear: '',
    sex: '',
    civilStatus: '',
    employmentStatus: '',
    educationalAttainment: '',
    spouseName: '',
    motherName: '',
    modeOfTransaction: 'walk-in',
    barangay: '',
    contactNumber: '',
    email: '',
    dswdMember: 'no',
    fourPMember: 'no',
    facilityHouseholdNo: '',
    cohabitation: '',
    familyMember: '',
    philhealthMember: 'no',
    philhealthNumber: '',
    primaryCareBenefitMember: 'no',
    philhealthCategory: '',
    notes: ''
  });

  if (currentPage === 'schedule-info') {
    return (
      <ScheduleInfo 
        onBack={() => setCurrentPage('home')}
        onNext={() => setCurrentPage('home')}
        selectedService={selectedService}
        setSelectedService={setSelectedService}
        formData={formData}
        setFormData={setFormData}
      />
    );
  }

  return (
    <div className="min-h-screen bg-white">
      <Header onScheduleClick={() => setCurrentPage('schedule-info')} />
      <HomePage onScheduleClick={() => setCurrentPage('schedule-info')} />
      <Footer />
    </div>
  );
}