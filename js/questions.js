/********************************************************************
*																	*
*	Javascript functionality to drive the Opinionnaire machine		*
*																	*
*     function GetBrowser()											*
*     function showHideDiv(el)										*
*     function checkAllQuestions(type,pageLoad)						*
*     function uncheckMaster(el,master)								*
*     function getCustomDemographicsJson()							*
*     function showCustomDemographics()								*
*     function additionalShowCustomDemographics(group_number)		*
*     function showCustomDemographicsOptions(select_id)				*
*																	*
*	  var cd_conditions												*
*	  var seg_conditions											*
*	  var sg_seg_conditions											*
*																	*
********************************************************************/

var cd_conditions = {
	"1":"is exactly equal to",
	"2":"is not exactly equal to",
	"3":"is one of the following answers",
	"4":"is not one of the following answers",
	"5":"> (greater than)",
	"6":"&ge; (greater than or equal to)",
	"7":"< (less than)",
	"8":"&le; (less than or equal to)",
	"9":"Date after or equal to",
	"10":"Date before or equal to",
	"11":"is answered",
	"12":"is not answered",
	"13":"matches regex pattern",
	"14":"does not match pattern",
	"15":"is true",
	"16":"is false",
	"17":"contains",
	"18":"is always true"
};

var seg_conditions = {
	"radio_cd_conditions":[3,4],
	"menu_cd_conditions":[3,4],
	"textbox_cd_conditions":[1,2,5,6,7,8,13,14]
};

var sg_seg_conditions = {
	"radio_cd_conditions":[3,4,11,12],
	"menu_cd_conditions":[3,4,11,12],
	"textbox_cd_conditions":[1,2,5,6,7,8,13,14]
};

var delete_button = true;

//multiple choice: 3,4,11,12
//text box: 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16
//ip address: 1,2,13,14
//country: 1,2,13,14
//region: 1,2,13,14
//postal code: 1,2,5,6,7,8,13,14
//city: 1,2,13,14
//url variable: 1,2,5,6,7,8,9,10,11,12,13,14,17
//topic id: 1,2,5,6,7,8,9,10,11,12,13,14,17
//survey id: 1,2,3,4,5,6,7,8,9,10,11,12,13,14,17

jQuery(document).ready(function() {
	checked = true
	jQuery(".value-statements-radio > input[id^='valuestatements_']:not(#valuestatements_master)").each(function(){
		if(!jQuery(this).prop('checked')) {
			checked = false;
		}
	})
	if(checked) {
		jQuery("#valuestatements_master").prop('checked',true);
	}
	jQuery("#choose_questions_custom_demographics").after('<a href="javascript:showDemographicCollection(false,true);" class="button cd_multi_func">Add Custom Demographic Collection</a><hr>');
	if(jQuery(".custom-demographics-radio").length == 1) {
		jQuery("#choose_questions_custom_demographics").hide();
	}
	jQuery("form").submit(function(){
		jQuery(".custom_demo_select").remove()
	})

	click_count = 0;
	jQuery("#choose_questions_custom_demographics").on('click', 'input:not(#custom_demographics_master)' , function(){
		div = this;
		if(jQuery(this).prop('checked') != false) {
			jQuery(this).val(click_count);
			jQuery("input[data-dbid='" + jQuery(this).attr('data-dbid') +  "']").each(function(){
				if(jQuery(this).attr('checked') && (div != this)) {
					jQuery(this).trigger('click');
				}
			});
			jQuery('.edit-custom-demographics-radio > input').each(function(){
				if(jQuery(this).prop('checked') == true) {
					jQuery(this).trigger('click');
				}
			})
			jQuery(this).prop('checked',true);
			cd_id   = click_count;
			if(jQuery(this).hasClass('dashicon-edit')) {
				newId   = showDemographicCollection(true);
			} else {
				newId   = showDemographicCollection();
			}
			myData  =
				jQuery(this).attr('data-new',newId);
			jQuery("#custom_demographics_2").parent('td').parent('tr').children('td').children().each(function(){
				jQuery(this).attr('data-new',newId);
			})
			theJson = jQuery(this).attr('data-json');
			theJson = JSON.parse(theJson);
			title   = theJson['title'];
			db_id   = jQuery(this).data('dbid');
			if(!(~jQuery(this).attr('id').indexOf('edit'))) {
				jQuery('#cd_select_div_collection_' + newId).hide();
			}
			jQuery("#cd_select_div_collection_" + cd_id + " > h3:first-child").text(title);
			jQuery("#custom_demo_select_" 		+ cd_id + "_title").val(title);
			ourLabel = jQuery(div).parent('td').parent('tr').children('td').children('label').prop('for');
			jQuery("#cd_select_div_collection_" + cd_id).append(jQuery("<input type='hidden' class='db_id' name='custom_demo_select[" + cd_id + "][db_id]' id='collection-" + newId + "' value='" + db_id + "'><input type='hidden' class='label-name' value='" + ourLabel + "'>"));
			i = 0;

			jQuery.each(theJson, function(key,group){
				increment = 0;
				if(key != 'title' && key != 'db_id') {
					if(key > 0) {
						showCustomDemographics(newId);
					}
					jQuery('#cd_select_div_collection_' + cd_id + ' > .collection-title').val(theJson['title']);
					jQuery.each(theJson[key], function(newKey, cd){
						if(newKey != 'title') {
							if(newKey > 0) {
								additionalShowCustomDemographics(cd_id,key);
							}
							cd_count = 0;
							theId = "custom_demo_select_" + cd_id + "_" + key + "_" + increment;
							jQuery.each(theJson[key][newKey],function(questionId){
								jQuery("#" + theId).val(questionId);
								showCustomDemographicsOptions(theId,cd_id,key,increment);
								jQuery.each(theJson[key][newKey][questionId]['inner_text'],function(valueId){
									jQuery("#inner_custom_demo_select_" + cd_id + "_" + key + "_" + increment + " input[name*='" + valueId + "']").prop("checked", true).val(theJson[key][newKey][questionId]['inner_text'][valueId]);
								})
								jQuery("#custom_demo_select_" + cd_id + "_" + key + "_" + increment + "_conditions").val(theJson[key][newKey][questionId]['conditions']);
								increment ++;
								cd_count++;
								i++;
							})
						} else {
							jQuery('#cd_select_div_group_' + cd_id + '_' + key + ' > .group-title').val(theJson[key]['title'])
						}
					})
				}
			})
		} else {
			this_val = jQuery(this).val();
			jQuery(".cd_multi_func").text('Add Custom Demographic Collection').prop('href','javascript:showDemographicCollection(false,true);')
			jQuery("#cd_select_div_collection_" + this_val).remove();
		}
		delete theJson;
	})

	jQuery('#custom_demographics_master').click(function(){
		checkAllQuestions('custom_demographics',true)
	})

	removeButton = 'a[id^="remove_custom_demographics"]';

	jQuery("#choose_questions_custom_demographics").on('click', removeButton , function(event){
    	event.preventDefault();
    	div = this;
		jQuery.post(ajaxurl, {'action':'get_custom_demos','remove':'true','db_id':jQuery(this).attr('data-dbid')}, function(response) {
			// console.log(response);
			removed = response.indexOf('1') >= 0;
			// console.log(div);
			if(removed) {
				jQuery(div).parent().parent().remove();
				i = 0;
				jQuery(removeButton).each(function(){i++});
				if(i == 0) {
					jQuery("#choose_questions_custom_demographics").toggle();
				}
			}
		})
	})

	if(typeof jQuery('#cd_list') !== 'undefined' && jQuery('#cd_list').val()) {
		cd_arr = jQuery('#cd_list').val().split(',');
	}
	if(typeof cd_arr !== 'undefined') {
		cd_arr.forEach(function(cd){
			jQuery('tr > td.custom-demographics-radio:first-child > input[data-dbid="' + cd + '"]').click();
		})
	}
	checked = true
	jQuery(".custom-demographics-radio > input[id^='custom_demographics_']:not(#custom_demographics_master)").each(function(){
		if(!jQuery(this).prop('checked')) {
			checked = false;
		} else {
		}
	})
	if(checked) {
		jQuery("#custom_demographics_master.primary-check-box").click();
	}

	jQuery("form").on('click', '.edit-title', function(){
		theTitle = jQuery(this).prev();
		theVal = jQuery(theTitle).val();
		jQuery(this).toggleClass('editing');
		jQuery(theTitle).focus().val(theVal);
		jQuery(theTitle).blur(function(){
	        jQuery(this).next(".editing").toggleClass('editing');
		})
	})

	/*jQuery("#check_all_primary").click(function() {
		if(jQuery("#check_all_primary").prop("checked") == true) {
			jQuery(".primary-check-box").each(function(ind,val) {
				jQuery("#" + val.id).prop("checked","checked");
			});
		} else {
			jQuery(".primary-check-box").each(function(ind,val) {
				jQuery("#" + val.id).prop("checked",false);
			});
		}
	});
	jQuery("#check_all_secondary").click(function() {
		if(jQuery("#check_all_secondary").prop("checked") == true) {
			jQuery(".secondary-check-box").each(function(ind,val) {
				jQuery("#" + val.id).prop("checked","checked");
			});
		} else {
			jQuery(".secondary-check-box").each(function(ind,val) {
				jQuery("#" + val.id).prop("checked",false);
			});
		}
	});*/
	// <h1 class='remove_parent'>x</h1>
	var myBrowser = GetBrowser();
	if(myBrowser == "IE" || myBrowser == "Safari") {
		jQuery(".ieSafari").show();
		jQuery(".notIeSafari").hide();
	} else {
		jQuery(".ieSafari").hide();
		jQuery(".notIeSafari").show();
	}
	zip_counter = 0;
	jQuery("#threshold_color_above").change(function() {
		jQuery("#threshold_color_above_sf").val(jQuery("#threshold_color_above").val());
	});
	jQuery("#threshold_color_below").change(function() {
		jQuery("#threshold_color_below_sf").val(jQuery("#threshold_color_below"));
	});
	jQuery("#threshold_color_above_sf").change(function() {
		jQuery("#threshold_color_above").val(jQuery("#threshold_color_above_sf").val());
	});
	jQuery("#threshold_color_below_sf").change(function() {
		jQuery("#threshold_color_below").val(jQuery("#threshold_color_below_sf").val());
	});
	jQuery(".no-link").click(function() {
		showHideDiv(this);
	});
	checkAllQuestions("demographics",true);
	checkAllQuestions("valuestatements",true);
	jQuery("td.value-statements-radio input").click(function() {
		uncheckMaster(this,"valuestatements_master");
	});
	jQuery("td.demographics-radio input").click(function() {
		uncheckMaster(this,"demographics_master");
	})
	jQuery("#choose_questions_custom_demographics").on('click', '.cd-check-box', function() {
		if(!jQuery(this).prop('checked')) {
			jQuery("#custom_demographics_master.primary-check-box").attr('checked',false);
		}
	})
});

function GetBrowser() {
	var isOpera = !!window.opera || navigator.userAgent.indexOf(' OPR/') >= 0;
    // Opera 8.0+ (UA detection to detect Blink/v8-powered Opera)
	var isFirefox = typeof InstallTrigger !== 'undefined';   // Firefox 1.0+
	var isSafari = Object.prototype.toString.call(window.HTMLElement).indexOf('Constructor') > 0;
	    // At least Safari 3+: "[object HTMLElementConstructor]"
	var isChrome = !!window.chrome && !isOpera;              // Chrome 1+
	var isIE = /*@cc_on!@*/false || !!document.documentMode; // At least IE6
	if(isIE) return "IE";
	if(isChrome) return "Chrome";
	if(isSafari) return "Safari";
	if(isFirefox) return "Firefox";
	if(isOpera) return "Opera";
	return "unknown";
}

function showHideDiv(el) {
	var divClass = el.id.replace("-button","");
	var curText = jQuery("#" + divClass + "-text").text();
	switch(curText) {
		case "Show":
			jQuery("." + divClass).show();
			jQuery("#" + divClass + "-text").text("Hide");
			break;
		case "Hide":
			jQuery("." + divClass).hide();
			jQuery("#" + divClass + "-text").text("Show");
			break;
	}
}

function checkAllQuestions(type,pageLoad) {
	if(type.length > 0) {
		if(jQuery("#" + type + "_master").length > 0) {
			if(jQuery("#" + type + "_master").prop("checked")) {
				switch(type) {
					case "demographics":
						jQuery("td.demographics-radio input").each(function() {
							jQuery(this).prop("checked","checked");
						});
						break;
					case "custom_demographics":
						if(jQuery("#choose_questions_custom_demographics tbody tr:first input").prop('checked')) {
							jQuery("td.custom-demographics-radio input:not(.dashicons,#custom_demographics_master)").each(function() {
								if(jQuery(this).prop('checked') == false) {
									jQuery(this).click();
								}
							});
						} else {
							jQuery("td.custom-demographics-radio input:not(.dashicons,#custom_demographics_master)").each(function() {
								if(jQuery(this).prop('checked') == true) {
									jQuery(this).click();
								}
							});
						}
						break;
					case "valuestatements":
						jQuery("td.value-statements-radio input").each(function() {
							jQuery(this).prop("checked","checked");
						});
						break;
				}
			} else if(!pageLoad) {
				switch(type) {
					case "demographics":
						jQuery("td.demographics-radio input").each(function() {
							jQuery(this).prop("checked",false);
						});
						break;
					case "custom_demographics":
						if(jQuery("#choose_questions_custom_demographics tbody tr:first input").prop('checked')) {
							jQuery("td.custom-demographics-radio input:not(.dashicons,#custom_demographics_master)").each(function() {
								if(jQuery(this).prop('checked') == false) {
									jQuery(this).click();
								}
							});
						} else {
							jQuery("td.custom-demographics-radio input:not(.dashicons,#custom_demographics_master)").each(function() {
								if(jQuery(this).prop('checked') == true) {
									jQuery(this).click();
								}
							});
						}
						break;
					case "valuestatements":
						jQuery("td.value-statements-radio input").each(function() {
							jQuery(this).prop("checked",false);
						});
						break;
				}
			}
		}
	}
}

function uncheckMaster(el,master) {
	if(jQuery("#" + el.id).prop("checked") == false) {
		jQuery("#" + master).prop("checked",false);
	}
}

function getCustomDemographicsJson() {
	var temp = jQuery("#cd_json").val();
	var cd_json = null;
	if(temp.length > 0) {
		cd_json = jQuery.parseJSON(temp);
	}
	return cd_json;
}

// This function is called when you add a new group.

function showCustomDemographics(cdc_count) {
	var cd_json = getCustomDemographicsJson();
	var cd_div = jQuery("#cd_select_div_collection_" + cdc_count);
	var group_div;
	var inner_count = 0;
	if(cd_json !== null) {
		var cd_count = jQuery("#cd_select_count_" + cdc_count).val();
		if(jQuery("#cd_select_div_group_" + cdc_count + "_" + cd_count).length === 0) {
			group_div = jQuery("<div></div>").prop({"id":"cd_select_div_group_" + cdc_count + "_" + cd_count,'class':'cd_group'});
			var cd_count_plus_one = parseInt(cd_count) + 1;
			var this_header = jQuery("<input type='text' class='group-title' name='custom_demo_select[" + cdc_count + "][" + cd_count + "][title]' value='[Custom Demographic Group Name Goes Here]'>");
			if(delete_button) {
				var this_remove = jQuery("<div class='dashicons dashicon-edit edit-group-title edit-title'></div><a href='#' class='remove_parent'>x</a><br>");
			} else {
				var this_remove = jQuery("<br>");
			}
			var this_hidden = jQuery("<input></input>").prop({"type":"hidden","id":"inner_cd_select_count_" + cd_count,"value":0});
			group_div.append(this_header,this_remove,this_hidden,"<br />");
			var this_option = "";
			var strippedString = "";
			var this_select_id = "custom_demo_select_" + cdc_count + "_" + cd_count + "_" + inner_count;
			var this_select_name = "custom_demo_select[" + cdc_count + "][" + cd_count + "][" + inner_count + "]";
			var partial_name = "custom_demo_select[" + cdc_count + "][" + cd_count + "]";
			var inner_cd_div = jQuery("<div></div>").prop({"id":"inner_" + this_select_id});
			var inner_inner_cd_div = jQuery("<div></div>").prop({"id":"inner_inner_" + this_select_id});
			var this_select = jQuery("<select></select>").prop({"id":this_select_id,"name":this_select_name,"class":"custom_demo_select"});
			this_option = jQuery("<option></option>").prop({"value":0}).text("Select...");
			this_select.append(this_option);
			jQuery.each(cd_json,function(ind,val) {
				if(val.title.English.length) {
					if(val.title.English.indexOf('</span>') != -1) {
						hasSpan = 1;
						strippedString = val.title.English.split("</span>");
					} else {
						hasSpan = 2;
						strippedString = val.title.English;
					}
					if((typeof strippedString[1] !== 'undefined') && (strippedString[1].length) && hasSpan == 1) {
						this_option = jQuery("<option></option>").prop({"value":ind}).text(strippedString[1]);
						this_select.append(this_option);
					} else if (hasSpan == 2) {
						this_option = jQuery("<option></option>").prop({"value":ind}).text(strippedString);
						this_select.append(this_option);
					}
				}
			});
			var addGroup = jQuery("<a></a>").prop({"id":"add_cd_" + cdc_count + "_" + cd_count,"class":"button custom_demographics add","href":"javascript:additionalShowCustomDemographics(" + cdc_count + "," + cd_count + ")"}).text("Add Additional Criteria to this Group");
			inner_cd_div.append(this_select,inner_inner_cd_div);
			group_div.append(inner_cd_div,addGroup);
			jQuery("#add_group_" + cdc_count).before(group_div);
			inner_count++;
			jQuery("#inner_cd_select_count_" + cd_count).val(inner_count);
			cd_count++;
			jQuery("#cd_select_count_" + cdc_count).val(cd_count);
			jQuery("#" + this_select_id).on("change",function() {
				showCustomDemographicsOptions(this_select_id,cdc_count,cd_count - 1, inner_count - 1)
			});
		}
	} else {
		cd_div.text("No Custom Demographics are available for this Survey Data.");
	}
}

function saveCd(cd,update) {
	divNameVals = [];
	validate = true
	jQuery("#cd_select_div_collection_" + cd + " .group-title").each(function(){
		if(jQuery.inArray(jQuery(this).val() , divNameVals) == -1) {
			divNameVals.push(jQuery(this).val());
		} else {
			alert("Group names must be unique");
			validate = false;
			return false;
		}
	})
	if(validate) {
		oldTitle = jQuery("#cd_select_div_collection_" + cd + " > .label-name").val();
		jQuery("cd_select_div_collection_" + cd + " > .label-name").remove();
		jQuery(".custom_demo_select").remove();
		if(typeof update === 'undefined') {
			update = false;
		}
		jQuery('#cd_select_div_collection_' + cd).wrap("<form id='cd_form" + cd + "'></form>");
		if(update) {
			jQuery("#cd_form" + cd).append(jQuery("<input type='hidden' name='update' value='true'>"));
		} else {
			jQuery("#cd_form" + cd).append(jQuery("<input type='hidden' name='insert' value='true'>"));
		}
		jQuery("#cd_form" + cd).append(jQuery("<input type='hidden' name='survey_id' value='" + jQuery('#survey_id').val() + "'>"));
		jQuery("#cd_form" + cd).append(jQuery("<input type='hidden' name='action' value='get_custom_demos'>"));
		var serialForm = jQuery("#cd_form" + cd).serialize();
		jQuery.post(
			ajaxurl,
			serialForm,
			function(response) {
				if(response.indexOf('0') == 0) {
					jQuery('#cd_select_div_collection_' + cd).remove();
					jQuery(".cd_multi_func").text('Add Custom Demographic Collection').prop('href','javascript:showDemographicCollection(false,true);')
					jQuery('label[for="' + oldTitle + '"]').parent('td').parent('tr').find('input').each(function(){
						if(jQuery(this).hasClass('dashicons') && jQuery(this).prop('checked') == true) {
							jQuery(this).prop('checked',false);
						}
					})
				} else {
					if(update) {
						jQuery('label[for="' + oldTitle + '"]').parent('td').parent('tr').find('input').each(function(){
							jQuery(this).attr("data-json",response.substring(0, response.length - 1));
							if(jQuery(this).hasClass('dashicons') && jQuery(this).prop('checked') == true) {
								jQuery(this).prop('checked',false);
							}
							jQuery('#cd_select_div_collection_' + cd).remove();
							jQuery(".cd_multi_func").text('Add Custom Demographic Collection').prop('href','javascript:showDemographicCollection(false,true);')
						})
						newJson = JSON.parse(response.substring(0, response.length - 1));
						jQuery('label[for="' + oldTitle + '"]').text(newJson['title']);
					} else {
						strArr = response.split('##|');
						db_id = strArr[1];
						rawJson = strArr[0];
						ourJson = JSON.parse(rawJson);
						if(jQuery("#choose_questions_custom_demographics").css('display') == 'none') {
							jQuery("#choose_questions_custom_demographics").toggle();
						}
						jQuery('#choose_questions_custom_demographics tbody').append("<tr><td class='choose-questions-radio custom-demographics-radio'><input type='checkbox' name='question_" + cd + "' id='custom_demographics_" + cd + "' value='" + cd + "' data-dbid='" + db_id + "' data-json='" + rawJson + "' class='cd-check-box primary-check-box'></td><td><label for='custom_demographics_" + cd + "'>" + ourJson['title'] + "</label></td><td class='edit-questions-radio edit-custom-demographics-radio'><input type='checkbox' class='dashicons dashicon-edit' name='question_" + cd + "' id='edit_custom_demographics_" + cd + "' value='2' data-dbid='" + db_id + "' data-json='" + rawJson + "'></td><td class='remove-questions-radio custom-demographics-radio'><a href='#' id='remove_custom_demographics_" + cd + "' data-dbid='" + db_id + "'>x</a></td></tr>")
					}
					jQuery('#cd_select_div_collection_' + cd).remove();
					jQuery(".cd_multi_func").text('Add Custom Demographic Collection').prop('href','javascript:showDemographicCollection(false,true);')
				}
			}
		);
		jQuery('#cd_select_div_collection_' + cd).unwrap();
	}
}

function removeCollection(cd) {
	jQuery('#cd_select_div_collection_' + cd).remove();
	jQuery(".cd_multi_func").text('Add Custom Demographic Collection').prop('href','javascript:showDemographicCollection(false,true);')
}

function showDemographicCollection(editCollection,insertCollection) {
	click_count ++;
	var cd_json    = getCustomDemographicsJson();
	var cdc_div    = jQuery(".cd_multi_func");
	var inner_count= 0;
	var group_div;
	jQuery('#save_cd, label[for="save_cd"]').show();
	if(cd_json !== null) {
		var cdc_count = jQuery("#cdc_select_count").val();
		if(jQuery("#cd_select_div_collection_" + cdc_count).length === 0) {

			var collection_div     = jQuery("<div id='cd_select_div_collection_" + cdc_count + "' class='cd_collection'></div>");
			var cdc_count_plus_one = parseInt(cdc_count) + 1;
			var this_header        = jQuery("<input type='text' name='custom_demo_select[" + cdc_count + "][title]' value='[Custom Demographic Collection Title Goes Here]' class='collection-title'>").val('[Custom Demographic Collection Title Goes Here]');
			if(delete_button) {
				var this_remove	   = jQuery("<div class='dashicons dashicon-edit edit-collection-title edit-title'></div><a href='javascript:removeCollection(" + cdc_count + ")' class='remove_parent_collection'>x</a><br>");
			} else {
				var this_remove	   = jQuery("<br>");
			}
			var this_hidden        = jQuery("<input></input>").prop({"type":"hidden","id":"inner_cdc_select_count_" + cdc_count,"value":0});
			collection_div.append(this_header,this_remove,this_hidden,"<br />");
			var this_option        = "";
			var strippedString     = "";
			var this_select_id     = "custom_demo_select_" + cdc_count + "_" + inner_count;
			var inner_cdc_div      = jQuery("<div></div>").prop({"id":"inner_" + this_select_id});
			var inner_inner_cdc_div= jQuery("<div></div>").prop({"id":"inner_inner_" + this_select_id});
			var addCollection      = jQuery("<a></a>").prop({"id":"add_group_" + cdc_count,"class":"button","href":"javascript:showCustomDemographics(" + cdc_count + ")"}).text("Add New Group to this Collection");
			var collectionCounter  = '<input type="hidden" id="cd_select_count_' + cdc_count + '" class="cd_select_count_' + cdc_count + '" value="0" />';

			collection_div.append(inner_cdc_div,addCollection,collectionCounter);
			cdc_div.before(collection_div);
			inner_count++;
			jQuery("#inner_cdc_select_count_" + cdc_count).val(inner_count);
			cdc_count++;
			jQuery("#cdc_select_count").val(cdc_count);
			old_cdc = cdc_count -1;
			showCustomDemographics(old_cdc);
			if(typeof editCollection !== 'undefined' && editCollection) {
				jQuery(".cd_multi_func").text('Save Custom Demographic Collection').prop('href','javascript:saveCd(' + old_cdc + ',true)');
			} else if(typeof insertCollection !== 'undefined' && insertCollection) {
				jQuery(".cd_multi_func").text('Save Custom Demographic Collection').prop('href','javascript:saveCd(' + old_cdc + ',false)');
			} else {
				jQuery(".cd_multi_func").text('Add Custom Demographic Collection').prop('href','javascript:showDemographicCollection(false,true);')
			}
			var count_id = old_cdc;
			jQuery("#custom_demo_select_div").on("keyup", jQuery("#custom_demo_select_" + count_id + "_title"), function(event) {
				jQuery("#cd_select_div_collection_" + count_id + " > h3:first-child").text(jQuery("#custom_demo_select_" + count_id + "_title").val());
			});
		};
	}
	jQuery('.cd_collection').on('click', '.remove_parent', function(event){
		event.preventDefault();
		jQuery(this).parent().remove();
	})
	return cdc_count -1;
}


function deselectValues(collection_number, group_number) {
	hasFailed = false;
	selected_vals = [];
	jQuery('.custom_demo_select[id*="select_' + collection_number + '_' + group_number + '"]').each(function(){
		selected_vals.push(jQuery(this).val())
	})
	jQuery('.custom_demo_select[id*="select_' + collection_number + '_' + group_number + '"]').each(function(){
		topSelect = this;
		jQuery(this).children('option').each(function(){
			if((jQuery(this).val() != jQuery(topSelect).val()) && (jQuery.inArray(jQuery(this).val(), selected_vals) != -1)) {
				jQuery(this).prop('disabled','disabled');
			}
		})

	})
}

function additionalShowCustomDemographics(collection_number,group_number) {
	jQuery("#add_cd_" + collection_number + "_" + group_number).remove();
	var cd_json            = getCustomDemographicsJson();
	var cd_div             = jQuery("#custom_demo_select_div");
	var this_remove        = jQuery("<a href='#' class='remove_parent'>x</a><br>");
	var group_div          = jQuery("#cd_select_div_group_" + collection_number + "_" + group_number);
	var inner_count        = jQuery("#inner_cd_select_count_" + group_number).val();
	var this_option        = "";
	var strippedString     = "";
	var this_select_id     = "custom_demo_select_" + collection_number + "_" + group_number + "_" + inner_count;
	var this_select_name   = 'custom_demo_select[' + collection_number + '][' + group_number + '][' + inner_count + ']';
	var name_partial       = 'custom_demo_select[' + collection_number + '][' + group_number + ']';
	var inner_cd_div       = jQuery("<div></div>").prop({"id":"inner_" + this_select_id});
	var inner_inner_cd_div = jQuery("<div></div>").prop({"id":"inner_inner_" + this_select_id});
	var this_select        = jQuery("<select></select>").prop({"id":this_select_id,"name":this_select_name,"class":"custom_demo_select"});
	this_option            = jQuery("<option></option>").prop({"value":0}).text("Select...");
	this_select.append(this_option);
	jQuery.each(cd_json,function(ind,val) {
		if(val.title.English.length > 0) {
			strippedString = val.title.English.split("</span>");
			if((typeof strippedString[1] !== 'undefined') && (strippedString[1].length) && hasSpan == 1) {
				this_option = jQuery("<option></option>").prop({"value":ind}).text(strippedString[1]);
				this_select.append(this_option);
			} else if (hasSpan == 2) {
				this_option = jQuery("<option></option>").prop({"value":ind}).text(strippedString);
				this_select.append(this_option);
			}
		}
	});
	var addGroup = jQuery("<a></a>").prop({"id":"add_cd_" + collection_number + "_" + group_number,"class":"button custom_demographics add","href":"javascript:additionalShowCustomDemographics(" + collection_number + "," + group_number + ")"}).text("Add Additional Criteria to this Group");
	inner_cd_div.append(this_select,this_remove,inner_inner_cd_div);
	inner_cd_div.prepend("<h3>and</h3><br />");
	group_div.append(inner_cd_div,addGroup);
	// cd_div.append(group_div);
	inner_count++;
	jQuery("#inner_cd_select_count_" + group_number).val(inner_count);
	jQuery("#" + this_select_id).on("change",function() {
		showCustomDemographicsOptions(this_select_id,collection_number,group_number,inner_count-1);
	});
	deselectValues(collection_number, group_number);
}

function showCustomDemographicsOptions(select_id,collection,group,inner_count) {
	var cd_id = jQuery("#" + select_id).val();
	var cd_json = getCustomDemographicsJson();
	select_name = "custom_demo_select[" + collection + "][" + group + "][" + inner_count + "][" + jQuery("#" + select_id).val() + "]";
	var new_id = "custom_demo_select_" + collection + "_" + group + "_" + inner_count + "_" +  jQuery("#" + select_id).val();
	var cont = jQuery("#inner_inner_" + select_id);
	var this_select = jQuery("<select></select>").prop({"id":select_id + "_conditions","name":select_name + "[conditions]","class":"custom_demo_conditions_select","style":"width:592px;"});
	var this_option = "";
	cont.empty();
	if(cd_json !== null && cd_id > 0) {
		jQuery.each(seg_conditions[cd_json[cd_id]["_subtype"] + "_cd_conditions"],function(ind1,val1) {
			this_option = jQuery("<option></option>").prop({"value":val1}).html(cd_conditions[val1]);
			this_select.append(this_option);
		});
		cont.append(this_select,"<br />");
		switch(cd_json[cd_id]["_subtype"]) {
			case "textbox":
				var this_input = jQuery("<input></input>").prop({"type":"text","class":"custom_demo_condtions_text","name":select_name + "[inner_text][" + zip_counter + "]","id":select_id + "_inner_text","size":92});
				zip_counter ++;
				cont.append(this_input);
				break;
			case "radio"://fall through
			case "menu":
				var i = 0;
				jQuery.each(cd_json[cd_id]["options"],function(ind,val) {
					var this_chbx = jQuery("<input></input>").prop({"type":"checkbox","value":val['value'],"class":"inner_" + select_id,"name":select_name + "[inner_text][" + val["id"] + "]","id":new_id + "_" + i});
					var this_label = jQuery("<label></label>").prop({"for":new_id + "_" + i}).text(val['value']);
					cont.append(this_chbx,this_label,"<br />");
					i++;
				});
				break;
		}
	}
	deselectValues(collection, group);
}
