$('form').on('submit', function() {
	const form = this;
	const $form = $(form);
    $.ajax({
        dataType: 'json',
        type: $form.attr('method'),
        url: $form.attr('action'),
        data: $form.serialize() + '&action=save',
        success: function (msg) {
			$('#reviews').html(msg.message);
        }
    });
    form.reset();
    return false;
});
