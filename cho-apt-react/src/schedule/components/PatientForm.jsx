import React from 'react';
import {
  SEX_OPTIONS,
  CIVIL_STATUS_OPTIONS,
  PHILHEALTH_OPTIONS,
  PHILHEALTH_CATEGORY_OPTIONS,
  STEPS
} from '../../data/constants';

export default function PatientForm({
  formData,
  onInputChange,
  onBlur,
  getFieldError,
  months,
  days,
  years
}) {
  return (
    <div className="step-content">
      <h2>{STEPS[1].label}</h2>
      <p className="step-subtitle">Please provide your details to proceed with the appointment booking.</p>

      <div className="form-container">
        <form className="details-form" onSubmit={(e) => e.preventDefault()}>
          <fieldset className="form-section">
            <legend className="form-section-title">Personal Information</legend>
            
            <div className="form-row">
              <div className="form-group">
                <label htmlFor="firstName">
                  First Name <span className="required">*</span>
                </label>
                <input
                  type="text"
                  id="firstName"
                  name="firstName"
                  value={formData.firstName || ''}
                  onChange={onInputChange}
                  onBlur={onBlur}
                  placeholder="Juan"
                  required
                />
                {getFieldError('firstName') && (
                  <span className="error-message">{getFieldError('firstName')}</span>
                )}
              </div>
              <div className="form-group">
                <label htmlFor="lastName">
                  Last Name <span className="required">*</span>
                </label>
                <input
                  type="text"
                  id="lastName"
                  name="lastName"
                  value={formData.lastName || ''}
                  onChange={onInputChange}
                  onBlur={onBlur}
                  placeholder="dela Cruz"
                  required
                />
                {getFieldError('lastName') && (
                  <span className="error-message">{getFieldError('lastName')}</span>
                )}
              </div>
            </div>

            <div className="form-row">
              <div className="form-group">
                <label htmlFor="middleName">Middle Name</label>
                <input
                  type="text"
                  id="middleName"
                  name="middleName"
                  value={formData.middleName || ''}
                  onChange={onInputChange}
                  onBlur={onBlur}
                  placeholder="Santos"
                />
              </div>
            </div>
          </fieldset>

          <fieldset className="form-section">
            <legend className="form-section-title">Contact Information</legend>

            <div className="form-row">
              <div className="form-group">
                <label htmlFor="email">
                  Email Address <span className="required">*</span>
                </label>
                <input
                  type="email"
                  id="email"
                  name="email"
                  value={formData.email || ''}
                  onChange={onInputChange}
                  onBlur={onBlur}
                  placeholder="juan@example.com"
                  required
                />
                {getFieldError('email') && (
                  <span className="error-message">{getFieldError('email')}</span>
                )}
              </div>
              <div className="form-group">
                <label htmlFor="contactNumber">
                  Contact Number <span className="required">*</span>
                </label>
                <input
                  type="tel"
                  id="contactNumber"
                  name="contactNumber"
                  value={formData.contactNumber || ''}
                  onChange={onInputChange}
                  onBlur={onBlur}
                  placeholder="09171234567"
                  required
                />
                {getFieldError('contactNumber') && (
                  <span className="error-message">{getFieldError('contactNumber')}</span>
                )}
              </div>
            </div>
          </fieldset>

          <fieldset className="form-section">
            <legend className="form-section-title">Demographic Information</legend>

            <div className="form-row">
              <div className="form-group">
                <label htmlFor="birthMonth">
                  Birth Month <span className="required">*</span>
                </label>
                <select
                  id="birthMonth"
                  name="birthMonth"
                  value={formData.birthMonth || ''}
                  onChange={onInputChange}
                  onBlur={onBlur}
                  required
                >
                  <option value="">Select Month</option>
                  {months.map((month, index) => (
                    <option key={month} value={index + 1}>
                      {month}
                    </option>
                  ))}
                </select>
                {getFieldError('birthMonth') && (
                  <span className="error-message">{getFieldError('birthMonth')}</span>
                )}
              </div>
              <div className="form-group">
                <label htmlFor="birthDay">
                  Birth Day <span className="required">*</span>
                </label>
                <select
                  id="birthDay"
                  name="birthDay"
                  value={formData.birthDay || ''}
                  onChange={onInputChange}
                  onBlur={onBlur}
                  required
                >
                  <option value="">Select Day</option>
                  {days.map(day => (
                    <option key={day} value={day}>
                      {day}
                    </option>
                  ))}
                </select>
                {getFieldError('birthDay') && (
                  <span className="error-message">{getFieldError('birthDay')}</span>
                )}
              </div>
              <div className="form-group">
                <label htmlFor="birthYear">
                  Birth Year <span className="required">*</span>
                </label>
                <select
                  id="birthYear"
                  name="birthYear"
                  value={formData.birthYear || ''}
                  onChange={onInputChange}
                  onBlur={onBlur}
                  required
                >
                  <option value="">Select Year</option>
                  {years.map(year => (
                    <option key={year} value={year}>
                      {year}
                    </option>
                  ))}
                </select>
                {getFieldError('birthYear') && (
                  <span className="error-message">{getFieldError('birthYear')}</span>
                )}
              </div>
            </div>

            <div className="form-row">
              <div className="form-group">
                <label htmlFor="sex">
                  Sex <span className="required">*</span>
                </label>
                <select
                  id="sex"
                  name="sex"
                  value={formData.sex || ''}
                  onChange={onInputChange}
                  onBlur={onBlur}
                  required
                >
                  <option value="">Select Sex</option>
                  {SEX_OPTIONS.map(option => (
                    <option key={option.value} value={option.value}>
                      {option.label}
                    </option>
                  ))}
                </select>
                {getFieldError('sex') && (
                  <span className="error-message">{getFieldError('sex')}</span>
                )}
              </div>
              <div className="form-group">
                <label htmlFor="civilStatus">Civil Status</label>
                <select
                  id="civilStatus"
                  name="civilStatus"
                  value={formData.civilStatus || ''}
                  onChange={onInputChange}
                >
                  <option value="">Select Status</option>
                  {CIVIL_STATUS_OPTIONS.map(option => (
                    <option key={option.value} value={option.value}>
                      {option.label}
                    </option>
                  ))}
                </select>
              </div>
            </div>

            <div className="form-row">
              <div className="form-group">
                <label htmlFor="barangay">Barangay</label>
                <input
                  type="text"
                  id="barangay"
                  name="barangay"
                  value={formData.barangay || ''}
                  onChange={onInputChange}
                  placeholder="e.g. Barangay 20"
                />
              </div>
            </div>
          </fieldset>

          <fieldset className="form-section">
            <legend className="form-section-title">PhilHealth Information</legend>

            <div className="form-row">
              <div className="form-group">
                <label htmlFor="philhealthMember">PhilHealth Member</label>
                <select
                  id="philhealthMember"
                  name="philhealthMember"
                  value={formData.philhealthMember || ''}
                  onChange={onInputChange}
                >
                  <option value="">Select</option>
                  {PHILHEALTH_OPTIONS.map(option => (
                    <option key={option.value} value={option.value}>
                      {option.label}
                    </option>
                  ))}
                </select>
              </div>
              <div className="form-group">
                <label htmlFor="philhealthNumber">PhilHealth Number</label>
                <input
                  type="text"
                  id="philhealthNumber"
                  name="philhealthNumber"
                  value={formData.philhealthNumber || ''}
                  onChange={onInputChange}
                  placeholder="e.g. 12-345678901-2"
                />
              </div>
            </div>

            <div className="form-row">
              <div className="form-group">
                <label htmlFor="primaryCareBenefitMember">Primary Care Benefit Member</label>
                <select
                  id="primaryCareBenefitMember"
                  name="primaryCareBenefitMember"
                  value={formData.primaryCareBenefitMember || ''}
                  onChange={onInputChange}
                >
                  <option value="">Select</option>
                  {PHILHEALTH_OPTIONS.map(option => (
                    <option key={option.value} value={option.value}>
                      {option.label}
                    </option>
                  ))}
                </select>
              </div>
              <div className="form-group">
                <label htmlFor="philhealthCategory">Category</label>
                <select
                  id="philhealthCategory"
                  name="philhealthCategory"
                  value={formData.philhealthCategory || ''}
                  onChange={onInputChange}
                >
                  <option value="">Select Category</option>
                  {PHILHEALTH_CATEGORY_OPTIONS.map(option => (
                    <option key={option.value} value={option.value}>
                      {option.label}
                    </option>
                  ))}
                </select>
              </div>
            </div>
          </fieldset>

          <fieldset className="form-section">
            <legend className="form-section-title">Additional Information</legend>

            <div className="form-row full-width">
              <div className="form-group">
                <label htmlFor="notes">Additional Notes (Optional)</label>
                <textarea
                  id="notes"
                  name="notes"
                  value={formData.notes || ''}
                  onChange={onInputChange}
                  placeholder="Any medical history, allergies, or additional information we should know?"
                  rows="4"
                />
              </div>
            </div>

            <div className="info-box">
              <p><strong>Next Step:</strong> Select your preferred appointment date.</p>
            </div>
          </fieldset>
        </form>
      </div>
    </div>
  );
}