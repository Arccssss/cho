import React from 'react';
import { MdArrowBack } from 'react-icons/md';

export default function Navigation({ title, onBack }) {
  return (
    <nav className="page-nav">
      <div className="page-nav-container">
        <button className="back-btn" onClick={onBack} title="Go back">
          <MdArrowBack size={24} />
          Back
        </button>
        <h3 className="page-nav-title">{title}</h3>
        <div className="nav-spacer"></div>
      </div>
    </nav>
  );
}