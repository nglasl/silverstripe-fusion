;(function($) {

	// Bind the events dynamically.

	$.entwine('ss', function($) {

		// Trigger page filtering, allowing CMS searchable content tagging.

		$('#pages-controller-cms-content .cms-search-form').entwine({
			onsubmit: function() {

				// Determine the filtering, allowing multiples so tags are parsed correctly.

				var filtering = {};
				$.each($(this).find(":input[value!='']").serializeArray(), function(key, filter) {

					filtering[filter.name] ? filtering[filter.name].push(filter.value) : filtering[filter.name] = [filter.value];
				});

				// Construct the URL parameters.

				var parameters = [];
				$.each(filtering, function(name, value) {

					parameters.push(name.replace('[]', '') + '=' + value.join(' '));
				});

				// Construct the URL using these parameters, where encoding is required twice.

				var url = encodeURI(encodeURI(this.attr('action') + '?' + parameters.join('&')));

				// Trigger the page filtering.

				this.closest('.cms-container').loadPanel(url, "", {}, true);
				return false;
			}
		});
	});

})(jQuery);
