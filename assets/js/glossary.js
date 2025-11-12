/**
 * Glossary JavaScript
 *
 * Handles hover-based popover display and accessibility features.
 *
 * @package PP_Glossary
 */

(function () {
	'use strict';

	let hideTimeout = null;
	const HIDE_DELAY = 500;

	/**
	 * Initialize glossary functionality when DOM is ready.
	 */
	function init() {
		setupHoverPopovers();
		setupSmoothScrolling();
		checkPopoverSupport();
	}

	/**
	 * Setup hover-based popovers for glossary terms.
	 */
	function setupHoverPopovers() {
		// Get all glossary term spans.
		const termSpans = document.querySelectorAll('[data-glossary-popover]');

		termSpans.forEach((span) => {
			const popoverId = span.getAttribute('data-glossary-popover');
			const popover = document.getElementById(popoverId);

			if (!popover) {
				return;
			}

			// Show popover on hover.
			span.addEventListener('mouseenter', () => {
				clearTimeout(hideTimeout);
				showPopover(popover, span);
			});

			// Hide popover when mouse leaves (with delay).
			span.addEventListener('mouseleave', () => {
				hideTimeout = setTimeout(() => {
					hidePopover(popover, span);
				}, HIDE_DELAY);
			});

			// Show popover on focus (keyboard navigation).
			span.addEventListener('focus', () => {
				clearTimeout(hideTimeout);
				showPopover(popover, span);
			});

			// Hide popover on blur.
			span.addEventListener('blur', () => {
				hideTimeout = setTimeout(() => {
					hidePopover(popover, span);
				}, HIDE_DELAY);
			});

			// Keep popover open when mouse is over it.
			popover.addEventListener('mouseenter', () => {
				clearTimeout(hideTimeout);
			});

			// Hide when mouse leaves popover.
			popover.addEventListener('mouseleave', () => {
				hideTimeout = setTimeout(() => {
					hidePopover(popover, span);
				}, HIDE_DELAY);
			});

			// Handle keyboard interactions.
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

			// Handle keyboard navigation within popover.
			popover.addEventListener('keydown', (event) => {
				if (event.key === 'Escape') {
					hidePopover(popover, span);
					span.focus();
				}
			});
		});
	}

	/**
	 * Show a popover.
	 *
	 * @param {HTMLElement} popover The popover element.
	 * @param {HTMLElement} trigger The trigger element.
	 */
	function showPopover(popover, trigger) {
		try {
			if (!popover.matches(':popover-open')) {
				popover.showPopover();
				trigger.setAttribute('aria-expanded', 'true');
			}
		} catch (error) {
			console.error('Error showing popover:', error);
		}
	}

	/**
	 * Hide a popover.
	 *
	 * @param {HTMLElement} popover The popover element.
	 * @param {HTMLElement} trigger The trigger element.
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
	 * Setup smooth scrolling for alphabet navigation.
	 */
	function setupSmoothScrolling() {
		const alphabetLinks = document.querySelectorAll('.glossary-alphabet a[href^="#"]');

		alphabetLinks.forEach((link) => {
			link.addEventListener('click', (event) => {
				event.preventDefault();
				const targetId = link.getAttribute('href').substring(1);
				const targetElement = document.getElementById(targetId);

				if (targetElement) {
					// Smooth scroll to the target.
					targetElement.scrollIntoView({
						behavior: 'smooth',
						block: 'start',
					});

					// Update focus for keyboard navigation.
					targetElement.setAttribute('tabindex', '-1');
					targetElement.focus();

					// Update URL without triggering scroll.
					if (history.pushState) {
						history.pushState(null, null, `#${targetId}`);
					}
				}
			});
		});
	}

	// Initialize when DOM is ready
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
