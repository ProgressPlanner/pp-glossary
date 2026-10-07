/**
 * Glossary List Block Editor
 */

(function (wp) {
	const { registerBlockType } = wp.blocks;
	const { __ } = wp.i18n;
	const { createElement: el } = wp.element;

	registerBlockType('your-glossary/glossary-list', {
		edit: function (props) {
			return el(
				'div',
				{
					className: 'your-glossary-block-editor',
					style: {
						padding: '2rem',
						backgroundColor: '#f9f9f9',
						border: '1px solid #ddd',
						borderRadius: '4px',
						textAlign: 'center',
					},
				},
				el('span', { className: 'dashicons dashicons-book-alt', style: { fontSize: '48px', color: '#0073aa' } }),
				el('h3', {}, __('Glossary List', 'your-glossary')),
				el('p', {}, __('The glossary entries will be displayed here on the frontend.', 'your-glossary'))
			);
		},

		save: function () {
			// Server-side rendered block
			return null;
		},
	});
})(window.wp);
