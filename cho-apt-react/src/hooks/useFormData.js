import { useState } from 'react';

/**
 * useFormData Hook
 * Handles form state and input changes
 */
export function useFormData(initialData = {}) {
  const [formData, setFormData] = useState({
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
    notes: '',
    ...initialData,
  });

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({
      ...prev,
      [name]: value,
    }));
  };

  const getFieldValue = (fieldName) => {
    return formData[fieldName] || '';
  };

  const setFieldValue = (fieldName, value) => {
    setFormData((prev) => ({
      ...prev,
      [fieldName]: value,
    }));
  };

  const resetForm = () => {
    setFormData({
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
      notes: '',
      ...initialData,
    });
  };

  return {
    formData,
    setFormData,
    handleInputChange,
    getFieldValue,
    setFieldValue,
    resetForm,
  };
}