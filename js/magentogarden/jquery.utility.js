/**
 * MagentoGarden
 *
 * @category    js
 * @package     magentogarden_ajaxcartlite
 * @copyright   Copyright (c) 2012 MagentoGarden Inc. (http://www.magentogarden.com)
 * @version		1.1
 * @author		MagentoGarden (coder@magentogarden.com)
 */

(function($) {
	$.fn.mg = {
		ajax : function(url, idata, callback) {
			$.ajax({
				url: url,
				data: idata,
				type: 'POST',
				beforeSend: function() { $.fancybox.showLoading(); }
			}).success(function(data) {
				callback(data);
				$.fancybox.hideLoading();
			});
		}
	};
})(jQuery);