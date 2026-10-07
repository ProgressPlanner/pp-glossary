/**
 * Admin synonyms management for Glossary entries.
 *
 * @package Your_Glossary
 */

document.addEventListener( 'DOMContentLoaded', function () {
	const addButton = document.getElementById( 'your-glossary-add-synonym' );
	const container = document.getElementById( 'your-glossary-synonyms-container' );

	if ( ! addButton || ! container ) {
		return;
	}

	const placeholder = addButton.dataset.placeholder || '';
	const removeText = addButton.dataset.removeText || 'Remove';

	addButton.addEventListener( 'click', function ( e ) {
		e.preventDefault();

		const row = document.createElement( 'div' );
		row.className = 'your-glossary-synonym-row';
		row.style.cssText = 'margin-bottom: 10px; display: flex; gap: 10px;';

		const input = document.createElement( 'input' );
		input.type = 'text';
		input.name = 'your_glossary_synonyms[]';
		input.value = '';
		input.className = 'regular-text';
		input.placeholder = placeholder;

		const button = document.createElement( 'button' );
		button.type = 'button';
		button.className = 'button your-glossary-remove-synonym';
		button.textContent = removeText;

		row.appendChild( input );
		row.appendChild( button );
		container.appendChild( row );
	} );

	container.addEventListener( 'click', function ( e ) {
		if ( e.target.classList.contains( 'your-glossary-remove-synonym' ) ) {
			e.preventDefault();
			e.target.closest( '.your-glossary-synonym-row' ).remove();
		}
	} );
} );
