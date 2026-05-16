# Health Portal Refactoring Guide
src/
├── assets/
│   └── logo.png
├── components/
│   ├── Header.jsx           # Global navigation bar (done)
│   ├── Footer.jsx           # Global footer (done)
│   └── Navigation.jsx       # Page navigation with back button (done)
├── data/
│   ├── constants.js         # All hardcoded data (services, time slots, etc.) (done)
│   └── validationRules.js   # Form validation rules (done)
├── hooks/
│   ├── useCalendar.js       # Calendar logic hook (done)
│   ├── useFormData.js       # Form handling hook (done)
│   └── useAppointmentFlow.js # Multi-step flow logic (done)
├── styles/
│   ├── common.css           # Global styles (buttons, forms, spacing) (done)
│   ├── navigation.css       # Navigation styles (shared) (done)
│   └── calendar.css         # Calendar-specific styles (done)
├── schedule/                # Feature folder: Appointment scheduling
│   ├── components/
│   │   ├── ServiceSelection.jsx      # Step 1 - Service cards (done)
│   │   ├── PatientForm.jsx    # Step 2 - Patient information (done)
│   │   ├── DateTimeSelector.jsx      # Step 3 - Calendar & time slots (done)
│   │   ├── Progress.jsx     # Step progress display (done)
│   │   ├── Calendar.jsx              # Reusable calendar component (done)
│   │   ├── TimeSlotGrid.jsx          # Reusable time slots component (removed)
│   │   └── Confirmation.jsx   # Booking summary display (done)
│   ├── ScheduleInfo.jsx     # Main container/orchestrator
│   ├── ScheduleInfo.css     # Schedule-specific styles
│   └── services.js          # Schedule feature business logic
├── home/                     # Feature folder: Home/landing page
│   ├── components/
│   │   ├── HeroSection.jsx (done)
│   │   ├── FeaturesGrid.jsx (done)
│   │   ├── InfoCards.jsx (done)
│   │   └── CTASection.jsx (done)
│   ├── HomePage.jsx (done)
│   └── HomePage.css (done)
├── App.jsx (done)
├── App.css
└── main.jsx
```

Rules:
1. Stick to Created files based on filetree
