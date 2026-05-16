// ========================================
// CONSTANTS & DATA
// ========================================

export const SERVICES = [
  {
    id: 'medical',
    title: 'Medical Consultation',
    description: 'General check-ups and diagnostic assessments with our licensed practitioners.',
    icon: '🏥'
  },
  {
    id: 'animal-bite',
    title: 'Animal Bite Treatment',
    description: 'Immediate intervention and post-exposure prophylaxis for animal-related injuries.',
    icon: '🐾'
  },
  {
    id: 'dental',
    title: 'Dental Care',
    description: 'Oral health assessments, cleanings, and emergency dental care procedures.',
    icon: '🦷'
  }
];

export const TIME_SLOTS = [
  { time: '8:00 AM', available: true },
  { time: '8:30 AM', available: true },
  { time: '9:00 AM', available: false },
  { time: '9:30 AM', available: true },
  { time: '10:00 AM', available: true },
  { time: '10:30 AM', available: true },
  { time: '11:00 AM', available: false },
  { time: '11:30 AM', available: true },
  { time: '1:00 PM', available: true },
  { time: '1:30 PM', available: true },
  { time: '2:00 PM', available: true },
  { time: '2:30 PM', available: false },
  { time: '3:00 PM', available: true },
  { time: '3:30 PM', available: true },
  { time: '4:00 PM', available: true },
  { time: '4:30 PM', available: true },
];

export const MONTH_NAMES = [
  'January', 'February', 'March', 'April', 'May', 'June',
  'July', 'August', 'September', 'October', 'November', 'December'
];

export const DAY_NAMES = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

export const FEATURES = [
  {
    icon: 'MdElectricBolt',
    title: 'Integrated Services',
    description: 'Connect seamlessly with laboratory, health, pharmacy services for complete care.'
  },
  {
    icon: 'FaRegClock',
    title: '24/7 Availability',
    description: 'Schedule appointments anytime, anywhere. No need to call during office hours or wait in long queues.'
  },
  {
    icon: 'FaShieldHalved',
    title: 'Secure & Private',
    description: 'Your personal information is protected with industry-standard security measures.'
  },
  {
    icon: 'HiOutlineDevicePhoneMobile',
    title: 'Mobile Friendly',
    description: 'Access our system from any device - smartphone, tablet, or desktop with responsive design.'
  },
  {
    icon: 'MdNotifications',
    title: 'Instant Confirmation',
    description: 'Receive immediate confirmation of your appointment booking with all details.'
  },
  {
    icon: 'MdOutlinePeopleAlt',
    title: 'Expert Care',
    description: 'Connect with qualified healthcare professionals for your medical needs.'
  },
];

export const CONTACT_INFO = {
  phone: '(034) 431-36-73',
  email: 'admin_bcho@gov.ph',
  office: 'Bacolod City Health Office',
  address: 'Galo BBB, Burgos Street, Barangay 20, Bacolod City',
  hours: 'Monday to Friday, 8AM-5PM'
};

export const STEPS = [
  { number: 1, label: 'Select Service' },
  { number: 2, label: 'Your Information' },
  { number: 3, label: 'Date & Confirm' }
];

export const APPOINTMENT_TIME = '8:00 AM - 5:00 PM';

export const SEX_OPTIONS = [
  { value: 'male', label: 'Male' },
  { value: 'female', label: 'Female' },
  { value: 'other', label: 'Other' }
];

export const CIVIL_STATUS_OPTIONS = [
  { value: 'single', label: 'Single' },
  { value: 'married', label: 'Married' },
  { value: 'divorced', label: 'Divorced' },
  { value: 'widowed', label: 'Widowed' }
];

export const PHILHEALTH_OPTIONS = [
  { value: 'no', label: 'No' },
  { value: 'yes', label: 'Yes' }
];

export const PHILHEALTH_CATEGORY_OPTIONS = [
  { value: 'individual', label: 'Individual' },
  { value: 'family', label: 'Family' },
  { value: 'senior', label: 'Senior Citizen' },
  { value: 'indigent', label: 'Indigent' }
];

// ========================================
// CALENDAR STEP INFORMATION
// NOTE: Calendar content with JSX is in calendarConfig.jsx
// ========================================

// Import and use in ScheduleInfo.jsx:
// import { CALENDAR_INFO_JSX } from '../data/calendarConfig';