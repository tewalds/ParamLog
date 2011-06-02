
jQuery.fn.save = function() {
	return this.each(function() {
		$.data(this, 'savedvalue', $(this).html());
	});
};
jQuery.fn.revert = function() {
	return this.each(function() {
		$(this).html($.data(this, 'savedvalue'));
	});
};
jQuery.fn.editbox = function(options) {
	return this.each(function() {
		var value = $(this).html();
		var input = $('<input>').val(value);
		input.width(options.width || "100%");
		$.each(options, function(k,v){
			input.attr(k, v);
		});
		$(this).html(input);
	});
};
jQuery.fn.input_obj = function(ret) {
	ret = ret || { };
	this.find('input').each(function() {
		ret[this.name] = this.value;
	});
	return ret;
};


