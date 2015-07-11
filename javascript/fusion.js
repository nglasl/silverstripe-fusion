;(function($) {

	// Bind the events dynamically.

	$.entwine('ss', function($) {

		// Trigger the page filtering, allowing CMS searchable content tagging.

		$('#pages-controller-cms-content form.cms-search-form').entwine({
			onsubmit: function() {

				// Determine the page filtering, allowing multiple tags.

				var form = $(this);
				var filtering = [];
				$.each(form.find(":input[value!='']").serializeArray(), function(key, filter) {

					filtering.push(filter.name + '=' + filter.value);
				});

				// Construct the URL, where "window.history" requires double encoding.

				var URL = encodeURI(encodeURI(form.attr('action') + '?' + filtering.join('&')));

				// Trigger the page filtering.

				form.closest('div.cms-container').loadPanel(URL, "", {}, true);
				return false;
			}
		});
	});

})(jQuery);
