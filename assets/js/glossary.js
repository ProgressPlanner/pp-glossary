/**
 * Glossary JavaScript
 *
 * Handles click-based popover display and accessibility features.
 *
 * @package PP_Glossary
 */

(function () {
	'use strict';

	/**
	 * Initialize glossary functionality when DOM is ready.
	 */
	function init() {
		setupClickPopovers();
		setupSmoothScrolling();
		maybeScrollOnPageLoad();
	}

	/**
	 * Setup click-based popovers for glossary terms.
	 */
	function setupClickPopovers() {
		// Get all glossary term spans.
		const termSpans = document.querySelectorAll('[data-glossary-popover]');

		termSpans.forEach((span) => {
			const popoverId = span.getAttribute('data-glossary-popover');
			const popover = document.getElementById(popoverId);

			if (!popover) {
				return;
			}

			// Toggle popover on click.
			span.addEventListener('click', () => {
				const isOpen = popover.matches(':popover-open');
				if (isOpen) {
					hidePopover(popover, span);
				} else {
					showPopover(popover, span);
				}
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
					scrollToTarget(targetElement);

					// Update URL without triggering scroll.
					if (history.pushState) {
						history.pushState(null, null, `#${targetId}`);
					}
				}
			});
		});
	}

	/**
	 * Scroll to the target element on page load.
	 */
	function maybeScrollOnPageLoad() {
		const alphabetContainer = document.querySelector(
			'.glossary-alphabet'
		);

		// We are not on a glossary page or there are no alphabet links, so we don't need to scroll.
		if ( ! alphabetContainer || ! window.location.hash ) {
			return;
		}

		const hash = window.location.hash.substring( 1 );
		const targetElement = document.getElementById( hash );

		if ( targetElement ) {
			// Smooth scroll to the target.
			scrollToTarget( targetElement );
		}
	}

	/**
	 * Scroll to the target element.
	 *
	 * @param {HTMLElement} targetElement The target element.
	 */
	function scrollToTarget( targetElement ) {
		if ( ! targetElement ) {
			return;
		}

		targetElement.scrollIntoView({
			behavior: 'smooth',
			block: 'start',
		} );

		// Update focus for keyboard navigation.
		targetElement.setAttribute( 'tabindex', '-1' );
		targetElement.focus();
	}

	// Initialize when DOM is ready
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
