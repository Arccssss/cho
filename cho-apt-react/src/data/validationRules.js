// ============================================
// FORM VALIDATION RULES
// ============================================

// Email validation regex
const EMAIL_REGEX = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

// Phone number validation regex (Philippine format)
const PHONE_REGEX = /^(\+63|0)?[0-9]{10}$/;

// Validation rules for form fields
export const VALIDATION_RULES = {
  firstName: {
    required: true,
    minLength: 2,
    maxLength: 50,
    message: 'First name must be between 2 and 50 characters'
  },
  lastName: {
    required: true,
    minLength: 2,
    maxLength: 50,
    message: 'Last name must be between 2 and 50 characters'
  },
  middleName: {
    required: false,
    maxLength: 50,
    message: 'Middle name must not exceed 50 characters'
  },
  email: {
    required: true,
    pattern: EMAIL_REGEX,
    message: 'Please enter a valid email address'
  },
  contactNumber: {
    required: true,
    pattern: PHONE_REGEX,
    message: 'Please enter a valid phone number'
  },
  birthMonth: {
    required: true,
    message: 'Birth month is required'
  },
  birthDay: {
    required: true,
    message: 'Birth day is required'
  },
  birthYear: {
    required: true,
    message: 'Birth year is required'
  },
  sex: {
    required: true,
    message: 'Sex is required'
  },
  civilStatus: {
    required: false,
    message: 'Civil status is optional'
  },
  barangay: {
    required: false,
    message: 'Barangay is optional'
  }
};

// Validate a single field
export const validateField = (name, value) => {
  const rule = VALIDATION_RULES[name];
  
  if (!rule) {
    return { isValid: true, error: null };
  }

  // Check if required
  if (rule.required && (!value || value.trim() === '')) {
    return {
      isValid: false,
      error: `${formatFieldName(name)} is required`
    };
  }

  // Check minimum length
  if (rule.minLength && value.length < rule.minLength) {
    return {
      isValid: false,
      error: rule.message
    };
  }

  // Check maximum length
  if (rule.maxLength && value.length > rule.maxLength) {
    return {
      isValid: false,
      error: rule.message
    };
  }

  // Check pattern (regex)
  if (rule.pattern && !rule.pattern.test(value)) {
    return {
      isValid: false,
      error: rule.message
    };
  }

  return { isValid: true, error: null };
};

// Validate entire form
export const validateForm = (formData, requiredFields = []) => {
  const errors = {};

  requiredFields.forEach(fieldName => {
    const { isValid, error } = validateField(fieldName, formData[fieldName]);
    if (!isValid) {
      errors[fieldName] = error;
    }
  });

  return {
    isValid: Object.keys(errors).length === 0,
    errors
  };
};

// Validate email specifically
export const validateEmail = (email) => {
  return EMAIL_REGEX.test(email);
};

// Validate phone number specifically
export const validatePhoneNumber = (phone) => {
  return PHONE_REGEX.test(phone);
};

// Validate date of birth
export const validateDateOfBirth = (month, day, year) => {
  if (!month || !day || !year) {
    return { isValid: false, error: 'Complete date of birth is required' };
  }

  try {
    const dob = new Date(year, month - 1, day);
    const today = new Date();
    
    // Check if date is valid
    if (dob.getMonth() !== month - 1) {
      return { isValid: false, error: 'Invalid date' };
    }

    // Check if date is in the past
    if (dob > today) {
      return { isValid: false, error: 'Date of birth cannot be in the future' };
    }

    // Check if person is at least 0 years old (reasonable validation)
    const age = today.getFullYear() - dob.getFullYear();
    if (age < 0) {
      return { isValid: false, error: 'Invalid date of birth' };
    }

    return { isValid: true, error: null };
  } catch (error) {
    return { isValid: false, error: 'Invalid date format' };
  }
};

// Format field name for error messages
const formatFieldName = (fieldName) => {
  return fieldName
    .replace(/([A-Z])/g, ' $1')
    .replace(/^./, str => str.toUpperCase())
    .trim();
};

// Check if form has all required fields filled
export const isFormComplete = (formData) => {
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

  for (let field of requiredFields) {
    if (!formData[field] || formData[field].trim() === '') {
      return false;
    }
  }

  return true;
};

// Sanitize form data before submission
export const sanitizeFormData = (formData) => {
  const sanitized = { ...formData };

  // Trim all string values
  Object.keys(sanitized).forEach(key => {
    if (typeof sanitized[key] === 'string') {
      sanitized[key] = sanitized[key].trim();
    }
  });

  return sanitized;
};

// Check if email already exists (placeholder for API call)
export const checkEmailExists = async (email) => {
  // TODO: Replace with actual API call to backend
  // const response = await fetch('/api/check-email', {
  //   method: 'POST',
  //   headers: { 'Content-Type': 'application/json' },
  //   body: JSON.stringify({ email })
  // });
  // const data = await response.json();
  // return data.exists;

  return false;
};