/**
 * Geostores javascript
 * 
 * @category   ExtensionsStore
 * @package	   ExtensionsStore_GeoStores
 * @author     Extensions Store <admin@extensions-store.com>
 */

function GeoStores($)
{
	var addNewRule = function(e)
	{
		e.preventDefault();
		
		var $ul = $(this).parents('ul.rules');
		var $li = $(this).parents('li.rule');
		//clone the row with add new button
		var li = $li.clone(true);
		$ul.append(li);
		
		//append rule params to row
		var $ruleParams = $('#ruleTemplate').find(">:first-child").clone(true);
		$li.append($ruleParams);
		$ruleParams.show();
		
		//hide add button
		$(this).hide();
	};
	
	var getSelect = function(selectType, $rule, $ruleParam)
	{
		var url = $('#selectUrl').val();
		var data = {};
	    data.form_key = window.FORM_KEY;
		data.store = $rule.find('.rule-store-select').val();
		data.select_type = selectType;
		data.val = $rule.find('.rule-'+selectType+'-select').val();
		data.op = $('.rule-op-select').val();
		$ruleParam.find('.rule-param-wait').show();
		$ruleParam.find('a.label').text('...');
		$ruleParam.find('select,input').attr('disabled','disabled');
		$('#saveRulesButton').addClass('disabled').attr('disabled','disabled');
		
		
		$.post(url, data, function(res){
			
			if (!res.error){
				
				$ruleParam.find('.rule-param-wait').hide();
				if (res.data.length>0){
					$ruleParam.find('select').show().html(res.data).removeAttr('disabled');
					$ruleParam.find('input').hide().attr('disabled','disabled');
				} else {
					$ruleParam.find('select').hide().attr('disabled','disabled');
					$ruleParam.find('input').show().removeAttr('disabled');
				}

				$('#saveRulesButton').removeClass('disabled').removeAttr('disabled');
				
			} else {
				if (typeof console == 'object'){
					console.log(res.data);
				}
			}
		});		
	};
	
	var selectStore = function(e)
	{
		e.preventDefault();

		var $rule = $(this).parents('.rule');
		var $ruleParam = $rule.find('.rule-op');
		$ruleParam.show();
	};	
	
	var selectOp = function(e)
	{
		e.preventDefault();

		var $rule = $(this).parents('.rule');
		var $ruleParam = $rule.find('.rule-country');
		$ruleParam.show();
		
		getSelect('op', $rule, $ruleParam);
	};		
	
	var selectCountry = function(e)
	{
		e.preventDefault();
		
		var $rule = $(this).parents('.rule');
		var $ruleParam = $rule.find('.rule-region');
		$ruleParam.show();
		$rule.find('.rule-redirect').show();
				
		getSelect('country', $rule, $ruleParam);
	};
	
	var selectRegion = function(e)
	{
		e.preventDefault();
		var $rule = $(this).parents('.rule');
		var $ruleParam = $rule.find('.rule-city');
		$ruleParam.show();
		$rule.find('.rule-redirect').show();

		getSelect('region', $rule, $ruleParam);
	};
	
	var selectCity = function(e)
	{
		e.preventDefault();
		
	};
	
	var removeRule = function(e)
	{
		e.preventDefault();
		
		var $li = $(this).parents('li.rule');
		$li.remove();
	};
	
	var showSelect = function(e)
	{
		e.preventDefault();
		$(this).hide().next('span.element').show();
	};
	
	var hideSelect = function(e)
	{
		e.preventDefault();
		var label = $(this).find('option:selected').text();
		label = label.trim();
		if (!label || label.match(/select/i)){
			label = '...';
		}
		$(this).parent('span.element').hide().prev('a.label').text(label).show();
	};
	
	return {
		
		init : function()
		{
			$('.rule-add').click(addNewRule);
			$('.rule-param a.label').click(showSelect);
			$('.rule-select').change(hideSelect).focusout(hideSelect);
			$('.rule-store-select').change(selectStore);
			$('.rule-op-select').change(selectOp);
			$('.rule-country-select').change(selectCountry);
			$('.rule-region-select').change(selectRegion);
			$('.rule-city-select').change(selectCity);
			$('.rule-remove').click(removeRule);
		}
		
	};
	
};	

if (!window.jQuery){
	document.write('<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js">\x3C/script><script>jQuery.noConflict(); var geoStores = GeoStores(jQuery);</script>');
	document.write('<link rel="stylesheet" href="//ajax.googleapis.com/ajax/libs/jqueryui/1.11.2/themes/smoothness/jquery-ui.css" />');
	document.write('<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.11.2/jquery-ui.min.js">\x3C/script>');
	
} else {
	var geoStores = GeoStores(jQuery);
}