export function GutenbergPrimaryTerm() {
	wp.hooks.addFilter(
		'editor.PostTaxonomyType',
		'wds/add-primary-terms',
		addPrimaryCategoryControl
	);
}
function addPrimaryCategoryControl(OriginalComponent) {
	const { useSelect, dispatch, withSelect } = wp.data;
	const { decodeEntities } = wp.htmlEntities;
	const { SelectControl } = wp.components;
	return (props) => {
		if ('category' !== props.slug) {
			return <OriginalComponent {...props} />;
		}

		const { terms } = useSelect((select) => {
			const { getEditedPostAttribute } = select('core/editor');
			return {
				terms: getEditedPostAttribute('categories'),
			};
		});

		const PrimaryCategoryControl = withSelect((select) => {
			const categoryOptions = [];
			if (terms) {
				terms.forEach((categoryId) => {
					const category = select('core').getEntityRecord(
						'taxonomy',
						'category',
						categoryId
					);
					if (category) {
						categoryOptions.push({
							label: decodeEntities(category.name),
							value: categoryId,
						});
					}
				});
			}

			return {
				categoryOptions,
				primaryCategory:
					select('core/editor').getEditedPostAttribute('meta')
						?.wds_primary_category,
			};
		})(({ categoryOptions, primaryCategory }) => {
			// Return early if the post doesn't have any categories currently selected.
			if (
				!terms.length ||
				!categoryOptions.length ||
				categoryOptions.length !== terms.length
			) {
				return null;
			}
			const selectFirstAvailable = () => {
				const value = categoryOptions[0].value;
				dispatch('core/editor').editPost({
					meta: { wds_primary_category: value },
				});
			};
			// If no primary category set is set,
			// or the current primary category is not among the selected categories,
			// select the first available category.
			if (0 === primaryCategory) {
				// This will also set the default category (typically "Uncategorized")
				// as the Primary on save if the post has no other categories set.
				selectFirstAvailable();
			} else if (!terms.find((t) => t === primaryCategory)) {
				selectFirstAvailable();
			}

			return (
				<div
					style={{
						marginTop: '1em',
					}}
				>
					<h3>Primary Category</h3>
					<div
						style={{
							margin: '-6px 0 0 -6px',
							maxHeight: '10.5em',
							overflow: 'auto',
							padding: '6px 0 2px 6px',
						}}
					>
						<SelectControl
							value={primaryCategory}
							options={categoryOptions}
							onChange={(option) => {
								dispatch('core/editor').editPost({
									meta: {
										wds_primary_category: Number(option),
									},
								});
							}}
							__nextHasNoMarginBottom
						/>
					</div>
				</div>
			);
		});
		return (
			<>
				<OriginalComponent {...props} />
				<PrimaryCategoryControl />
			</>
		);
	};
}
