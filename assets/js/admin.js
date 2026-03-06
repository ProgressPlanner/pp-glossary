/**
 * Admin synonyms management for Glossary entries.
 *
 * @package PP_Glossary
 */

document.addEventListener( 'DOMContentLoaded', function () {
	const addButton = document.getElementById( 'pp-glossary-add-synonym' );
	const container = document.getElementById( 'pp-glossary-synonyms-container' );

	if ( ! addButton || ! container ) {
		return;
	}

	const placeholder = addButton.dataset.placeholder || '';
	const removeText = addButton.dataset.removeText || 'Remove';

	addButton.addEventListener( 'click', function ( e ) {
		e.preventDefault();

		const row = document.createElement( 'div' );
		row.className = 'pp-glossary-synonym-row';
		row.style.cssText = 'margin-bottom: 10px; display: flex; gap: 10px;';

		const input = document.createElement( 'input' );
		input.type = 'text';
		input.name = 'pp_glossary_synonyms[]';
		input.value = '';
		input.className = 'regular-text';
		input.placeholder = placeholder;

		const button = document.createElement( 'button' );
		button.type = 'button';
		button.className = 'button pp-glossary-remove-synonym';
		button.textContent = removeText;

		row.appendChild( input );
		row.appendChild( button );
		container.appendChild( row );
	} );

	container.addEventListener( 'click', function ( e ) {
		if ( e.target.classList.contains( 'pp-glossary-remove-synonym' ) ) {
			e.preventDefault();
			e.target.closest( '.pp-glossary-synonym-row' ).remove();
		}
	} );
} );
