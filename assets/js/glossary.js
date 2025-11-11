/**
 * Glossary JavaScript
 *
 * Handles hover-based popover display and accessibility features
 *
 * @package PP_Glossary
 */

(function () {
	'use strict';

	let hideTimeout = null;
	const HIDE_DELAY = 300; // ms delay before hiding popover

	/**
	 * Initialize glossary functionality when DOM is ready
	 */
	function init() {
		setupHoverPopovers();
		setupSmoothScrolling();
		checkPopoverSupport();
	}

	/**
	 * Setup hover-based popovers for glossary terms
	 */
	function setupHoverPopovers() {
		// Get all glossary term spans
		const termSpans = document.querySelectorAll('[data-glossary-popover]');

		termSpans.forEach((span) => {
			const popoverId = span.getAttribute('data-glossary-popover');
			const popover = document.getElementById(popoverId);

			if (!popover) {
				return;
			}

			// Show popover on hover
			span.addEventListener('mouseenter', () => {
				clearTimeout(hideTimeout);
				showPopover(popover, span);
			});

			// Hide popover when mouse leaves (with delay)
			span.addEventListener('mouseleave', () => {
				hideTimeout = setTimeout(() => {
					hidePopover(popover, span);
				}, HIDE_DELAY);
			});

			// Show popover on focus (keyboard navigation)
			span.addEventListener('focus', () => {
				clearTimeout(hideTimeout);
				showPopover(popover, span);
			});

			// Hide popover on blur
			span.addEventListener('blur', () => {
				hideTimeout = setTimeout(() => {
					hidePopover(popover, span);
				}, HIDE_DELAY);
			});

			// Keep popover open when mouse is over it
			popover.addEventListener('mouseenter', () => {
				clearTimeout(hideTimeout);
			});

			// Hide when mouse leaves popover
			popover.addEventListener('mouseleave', () => {
				hideTimeout = setTimeout(() => {
					hidePopover(popover, span);
				}, HIDE_DELAY);
			});

			// Handle keyboard interactions
			span.addEventListener('keydown', (event) => {
				if (event.key === 'Enter' || event.key === ' ') {
					event.preventDefault();
					const isOpen = popover.matches(':popover-open');
					if (isOpen) {
						hidePopover(popover, span);
					} else {
						showPopover(popover, span);
					}
				} else if (event.key === 'Escape') {
					hidePopover(popover, span);
				}
			});

			// Handle keyboard navigation within popover
			popover.addEventListener('keydown', (event) => {
				if (event.key === 'Escape') {
					hidePopover(popover, span);
					span.focus();
				}
			});
		});
	}

	/**
	 * Show a popover
	 *
	 * @param {HTMLElement} popover The popover element
	 * @param {HTMLElement} trigger The trigger element
	 */
	function showPopover(popover, trigger) {
		try {
			if (!popover.matches(':popover-open')) {
				popover.showPopover();
				trigger.setAttribute('aria-expanded', 'true');
				positionPopover(popover, trigger);
			}
		} catch (error) {
			console.error('Error showing popover:', error);
		}
	}

	/**
	 * Hide a popover
	 *
	 * @param {HTMLElement} popover The popover element
	 * @param {HTMLElement} trigger The trigger element
	 */
	function hidePopover(popover, trigger) {
		try {
			if (popover.matches(':popover-open')) {
				popover.hidePopover();
				trigger.setAttribute('aria-expanded', 'false');
			}
		} catch (error) {
			console.error('Error hiding popover:', error);
		}
	}

	/**
	 * Position popover near the trigger element
	 *
	 * @param {HTMLElement} popover The popover element
	 * @param {HTMLElement} trigger The trigger element
	 */
	function positionPopover(popover, trigger) {
		const triggerRect = trigger.getBoundingClientRect();
		const popoverRect = popover.getBoundingClientRect();
		const viewportWidth = window.innerWidth;
		const viewportHeight = window.innerHeight;
		const scrollY = window.scrollY || window.pageYOffset;
		const scrollX = window.scrollX || window.pageXOffset;

		// Small offset from trigger (reduced from 8px to 4px)
		const offset = 4;

		// Calculate position below the trigger (using fixed positioning, no scroll offset needed)
		let top = triggerRect.bottom + offset;
		let left = triggerRect.left;

		// Adjust if popover would overflow right edge
		if (left + popoverRect.width > viewportWidth - 16) {
			left = Math.max(16, viewportWidth - popoverRect.width - 16);
		}

		// Adjust if popover would overflow left edge
		if (left < 16) {
			left = 16;
		}

		// If popover would overflow bottom, position above trigger
		if (triggerRect.bottom + popoverRect.height + offset > viewportHeight - 16) {
			top = triggerRect.top - popoverRect.height - offset;
			// If it would overflow the top too, just position below anyway
			if (top < 16) {
				top = triggerRect.bottom + offset;
			}
		}

		// Apply positioning (fixed position doesn't need scroll offsets)
		popover.style.top = top + 'px';
		popover.style.left = left + 'px';
	}

	/**
	 * Setup smooth scrolling for alphabet navigation
	 */
	function setupSmoothScrolling() {
		const alphabetLinks = document.querySelectorAll('.glossary-alphabet a[href^="#"]');

		alphabetLinks.forEach((link) => {
			link.addEventListener('click', (event) => {
				event.preventDefault();
				const targetId = link.getAttribute('href').substring(1);
				const targetElement = document.getElementById(targetId);

				if (targetElement) {
					// Smooth scroll to the target
					targetElement.scrollIntoView({
						behavior: 'smooth',
						block: 'start',
					});

					// Update focus for keyboard navigation
					targetElement.setAttribute('tabindex', '-1');
					targetElement.focus();

					// Update URL without triggering scroll
					if (history.pushState) {
						history.pushState(null, null, `#${targetId}`);
					}
				}
			});
		});
	}

	/**
	 * Polyfill check for Popover API
	 */
	function checkPopoverSupport() {
		if (!HTMLElement.prototype.hasOwnProperty('popover')) {
			console.warn(
				'Popover API is not supported in this browser. Consider adding a polyfill for older browsers.'
			);
		}
	}

	// Initialize when DOM is ready
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
