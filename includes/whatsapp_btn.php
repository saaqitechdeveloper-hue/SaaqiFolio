<?php
/**
 * SAAQIFOLIO - Floating WhatsApp Contact Button Component
 * Responsive, Animated Pulse, Glass Tooltip
 */
?>
<style>
  /* ===== WhatsApp Floating Button ===== */
  .whatsapp-fixed-btn {
    position: fixed;
    left: unset;
    right: 10px;
    bottom: 100px;
    width: 62px;
    height: 62px;
    background: #25D366;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    z-index: 9999;
    box-shadow: 0 4px 15px rgba(37, 211, 102, 0.5);
    animation: wa-bounce 2.5s ease-in-out infinite;
    transition: transform 0.25s ease, box-shadow 0.25s ease;
  }

  .whatsapp-fixed-btn svg {
    width: 34px;
    height: 34px;
    fill: #fff;
    position: relative;
    z-index: 2;
  }

  /* Glow pulse rings */
  .whatsapp-fixed-btn::before,
  .whatsapp-fixed-btn::after {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    border-radius: 50%;
    background: #25D366;
    opacity: 0.6;
    z-index: 1;
    animation: wa-ripple 2s linear infinite;
  }

  .whatsapp-fixed-btn::after {
    animation-delay: 1s;
  }

  @keyframes wa-ripple {
    0% {
      transform: scale(1);
      opacity: 0.6;
    }
    100% {
      transform: scale(1.9);
      opacity: 0;
    }
  }

  @keyframes wa-bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-6px); }
  }

  .whatsapp-fixed-btn:hover {
    box-shadow: 0 6px 22px rgba(37, 211, 102, 0.8);
    transform: translateY(-4px) scale(1.05);
  }

  /* Tooltip */
  .whatsapp-fixed-btn .wa-tooltip {
    position: absolute;
    right: 75px;
    left: auto;
    top: 50%;
    transform: translateY(-50%);
    background: #111;
    color: #fff;
    padding: 8px 14px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 500;
    letter-spacing: 0.2px;
    white-space: nowrap;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.25s ease, visibility 0.25s ease, transform 0.25s ease;
    z-index: 3;
    pointer-events: none;
    box-shadow: 0 4px 14px rgba(0,0,0,0.4);
  }

  .whatsapp-fixed-btn .wa-tooltip::after {
    content: "";
    position: absolute;
    left: 100%;
    top: 50%;
    transform: translateY(-50%);
    border-width: 6px;
    border-style: solid;
    border-color: transparent transparent transparent #111;
  }

  .whatsapp-fixed-btn:hover .wa-tooltip {
    opacity: 1;
    visibility: visible;
    transform: translateY(-50%) translateX(-4px);
  }

  @media (max-width: 900px) {
    .whatsapp-fixed-btn {
      transform: translate(-20px, 13px) scale(0.6) !important;
      left: 20px;
      right: unset;
      bottom: 20px;
      animation: none !important;
    }
    .whatsapp-fixed-btn svg {
      width: 32px;
      height: 32px;
    }
    .whatsapp-fixed-btn .wa-tooltip {
      display: none !important;
    }
  }
</style>

<!-- ===== WhatsApp Button Markup ===== -->
<a
  href="https://wa.me/923453188326?text=Welcome%20to%20Saaqifolio%20What%20Can%20I%20Help%20You"
  class="whatsapp-fixed-btn"
  target="_blank"
  rel="noopener noreferrer"
  aria-label="Chat on WhatsApp"
  title="Chat with us on WhatsApp">

  <span class="wa-tooltip">Chat with us</span>

  <svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg">
    <path d="M16.001 3C9.373 3 4 8.373 4 15c0 2.386.7 4.607 1.902 6.474L4 29l7.727-1.867A11.94 11.94 0 0 0 16.001 27C22.628 27 28 21.627 28 15S22.628 3 16.001 3zm0 21.75c-1.96 0-3.79-.55-5.35-1.5l-.383-.228-4.59 1.108 1.127-4.472-.25-.397A9.72 9.72 0 0 1 5.25 15c0-5.936 4.815-10.75 10.751-10.75S26.75 9.064 26.75 15 21.937 24.75 16.001 24.75z"/>
    <path d="M21.62 17.85c-.303-.152-1.792-.884-2.07-.985-.278-.101-.48-.152-.682.152-.202.303-.783.985-.96 1.187-.177.202-.354.227-.657.076-.303-.152-1.278-.471-2.434-1.503-.9-.803-1.508-1.795-1.685-2.098-.177-.303-.019-.467.133-.618.137-.136.303-.354.454-.53.152-.177.202-.303.303-.505.101-.202.05-.379-.025-.53-.076-.152-.682-1.645-.935-2.253-.246-.591-.497-.511-.682-.52-.177-.008-.379-.01-.581-.01-.202 0-.53.076-.808.379-.278.303-1.06 1.036-1.06 2.528s1.086 2.933 1.238 3.136c.152.202 2.138 3.263 5.182 4.576.724.313 1.29.5 1.73.64.727.231 1.388.198 1.91.12.583-.087 1.792-.732 2.044-1.439.253-.707.253-1.313.177-1.439-.076-.126-.278-.202-.581-.354z"/>
  </svg>
</a>
