function change_opacity() { 
	var t_output = jQuery("#t_output")[0];
	var opacity = t_output.innerHTML;
	if(/^(\d(\.\d)?)$/.test(opacity)) {
		jQuery(".opin-table-color").each(function(ind,val) {
			jQuery(val).css("opacity",opacity);
		});
		jQuery(".preview-color").each(function(ind2,val2) {
			jQuery(val2).css("opacity",opacity);
		});
	}
}
jQuery(function($) {
	var t_output = $("#t_output")[0];
	var st_output = $("#st_output")[0];
	$(document).on('input', 'input[type="range"]', function(e) {
		var temp = e.target.value;
		switch(e.target.id) {
			case "transparency":
				if(temp === "1") {
					temp = "1.0";
				} else if(temp.length > 3) {
					temp = normalizeOutput(temp);
				}
				t_output.innerHTML = temp;
				jQuery(".opin-table-color").each(function(ind,val) {
					jQuery(val).css("opacity",temp);
				});
				jQuery(".preview-color").each(function(ind2,val2) {
					jQuery(val2).css("opacity",temp);
				});
				break;
			case "significant_threshold":
				st_output.innerHTML = temp;
				break;
			case "colorization_threshold":
				ct_output.innerHTML = temp;
				break;
		}
	});

	$('input[type=range]').rangeslider({
		polyfill: true
	});
});
jQuery(document).ready(function($) {
	jQuery("#transparency").change(function() {
		change_opacity();
	});
	jQuery(".transparency-modify").on("click",function() {
		change_opacity();
	});
	change_opacity();
	$(".transparency-modify").on("click",function() {
		modifyTransparency($(this).val());
	});
	$(".sig-threshold-modify").on("click",function() {
		modifySigThreshold($(this).val());
	});
	$(".col-threshold-modify").on("click",function() {
		modifyColThreshold($(this).val());
	});
});
function getAttr(el) {
	var step = jQuery("#" + el).attr("step");
	var min = jQuery("#" + el).attr("min");
	var max = jQuery("#" + el).attr("max");
	return [step,min,max];
}
function modifyTransparency(val) {
	var t_output = jQuery("#t_output")[0];
	var tAttr = getAttr("transparency");
	var currVal = jQuery("#transparency").val();
	switch(val) {
		case "-":
			if(parseFloat(currVal) > parseFloat(tAttr[1])) {
				var newOutput = parseFloat(currVal) - parseFloat(tAttr[0]);
				newOutput = normalizeOutput(newOutput.toString());
				jQuery("#transparency").val(newOutput);
				t_output.innerHTML = newOutput;
			}
			break;
		case "+":
			if(parseFloat(currVal) < parseFloat(tAttr[2])) {
				var newOutput = parseFloat(currVal) + parseFloat(tAttr[0]);
				newOutput = normalizeOutput(newOutput.toString());
				jQuery("#transparency").val(newOutput);
				t_output.innerHTML = newOutput;
			}
			break;
	}
}
function normalizeOutput(newOutput) {
	if(newOutput.length > 0) {
		return parseFloat(newOutput).toFixed(1);
	}
	return false;
}
function modifySigThreshold(val) {
	var st_output = jQuery("#st_output")[0];
	var stAttr = getAttr("significant_threshold");
	var currVal = jQuery("#significant_threshold").val();
	switch(val) {
		case "-":
			if(parseInt(currVal) > parseInt(stAttr[1])) {
				var newOutput = parseInt(currVal) - parseInt(stAttr[0]);
				jQuery("#significant_threshold").val(newOutput);
				st_output.innerHTML = newOutput;
			}
			break;
		case "+":
			if(parseInt(currVal) < parseInt(stAttr[2])) {
				var newOutput = parseInt(currVal) + parseInt(stAttr[0]);
				jQuery("#significant_threshold").val(newOutput);
				st_output.innerHTML = newOutput;
			}
			break;
	}
}
function modifyColThreshold(val) {
	var ct_output = jQuery("#ct_output")[0];
	var ctAttr = getAttr("colorization_threshold");
	var currVal = jQuery("#colorization_threshold").val();
	switch(val) {
		case "-":
			if(parseInt(currVal) > parseInt(ctAttr[1])) {
				var newOutput = parseInt(currVal) - parseInt(ctAttr[0]);
				jQuery("#colorization_threshold").val(newOutput);
				ct_output.innerHTML = newOutput;
			}
			break;
		case "+":
			if(parseInt(currVal) < parseInt(ctAttr[2])) {
				var newOutput = parseInt(currVal) + parseInt(ctAttr[0]);
				jQuery("#colorization_threshold").val(newOutput);
				ct_output.innerHTML = newOutput;
			}
			break;
	}
}