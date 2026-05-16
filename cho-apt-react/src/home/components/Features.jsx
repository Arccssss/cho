import React from 'react';
import { MdOutlinePeopleAlt, MdNotifications, MdElectricBolt, MdCheck } from 'react-icons/md';
import { FaShieldHalved, FaRegClock } from "react-icons/fa6";
import { HiOutlineDevicePhoneMobile } from "react-icons/hi2";
import { FEATURES } from '../../data/constants';

// Icon mapping for dynamic icon rendering
const ICON_MAP = {
  'MdElectricBolt': MdElectricBolt,
  'FaRegClock': FaRegClock,
  'FaShieldHalved': FaShieldHalved,
  'HiOutlineDevicePhoneMobile': HiOutlineDevicePhoneMobile,
  'MdNotifications': MdNotifications,
  'MdOutlinePeopleAlt': MdOutlinePeopleAlt,
};

export default function Features() {
  return (
    <section id="features" className="features-section">
      <div className="container">
        <h2>WHY CHOOSE OUR SYSTEM?</h2>
        <div className="features-grid">
          {FEATURES.map((feature, idx) => {
            const Icon = ICON_MAP[feature.icon];
            return (
              <div key={idx} className="feature-card">
                <div className="feature-icon">
                  {Icon && <Icon size={40} />}
                </div>
                <div className="feature-content">
                  <h3>{feature.title}</h3>
                  <p>{feature.description}</p>
                </div>
              </div>
            );
          })}
        </div>
      </div>
    </section>
  );
}