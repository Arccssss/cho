import React from 'react';
import logo from '../assets/logo.png';

export default function Header({ onScheduleClick }) {
  React.useEffect(() => {
    const handleScroll = () => {
      const heroSection = document.querySelector('.hero-section');
      const nav = document.querySelector('header nav');
      
      if (heroSection && nav) {
        const heroBottom = heroSection.offsetTop + heroSection.offsetHeight;
        const isInHero = window.scrollY < heroBottom;
        
        if (isInHero) {
          nav.classList.add('hero-nav');
        } else {
          nav.classList.remove('hero-nav');
        }
      }
    };
    
    window.addEventListener('scroll', handleScroll);
    handleScroll();
    
    return () => window.removeEventListener('scroll', handleScroll);
  }, []);

  return (
    <header>
      <nav>
        <div className="nav-container">
          <img src={logo} alt="Health Portal Logo" className="nav-logo-img" />
          <div className="nav-links">
            <a href="#features">Features</a>
            <a href="#information">Information</a>
            <button className="btn-blue" onClick={onScheduleClick}>Schedule</button>
          </div>
        </div>
      </nav>
    </header>
  );
}