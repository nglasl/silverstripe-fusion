;(function($) {

	// Bind the events dynamically.

	$.entwine('ss', function($) {

		// Trigger the page filtering, allowing CMS searchable content tagging.

		$('#pages-controller-cms-content form.cms-search-form').entwine({
			onsubmit: function() {

				// Determine the filtering, allowing multiples so tags are parsed correctly.

				var form = $(this);
				var filtering = {};
				$.each(form.find(":input[value!='']").serializeArray(), function(key, filter) {

					filtering[filter.name] ? filtering[filter.name].push(filter.value) : filtering[filter.name] = [filter.value];
				});

				// Construct the URL parameters.

				var parameters = [];
				$.each(filtering, function(name, value) {

					parameters.push(name.replace('[]', '') + '=' + value.join(' '));
				});

				// Construct the URL using these parameters, where encoding is required twice to match the previous behaviour.

				var URL = encodeURI(encodeURI(form.attr('action') + '?' + parameters.join('&')));

				// Trigger the page filtering.

				form.closest('div.cms-container').loadPanel(URL, "", {}, true);
				return false;
			}
		});
	});

})(jQuery);
