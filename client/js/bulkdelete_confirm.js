(function ($) {

	$('.cms-edit-form .bulkdelete_button').click(function (e) {

		var button = $(e.target);
		var action = $.trim(button.text());
		var message = 'Are you sure you want to ' + action.toLowerCase() + '?';

		if (!confirm(message)) {
			e.preventDefault();
			return false;
		} else {
			this._super(e);
		}
	});

}(jQuery));
