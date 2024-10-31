function generate_pol(cat,demo,vs,pol,con,ao) {
	var checkDiv = jQuery("#pol-" + demo + "-" + vs + "-" + cat + "-bargraphs");
	var myButton = jQuery("#button-" + demo + "-" + vs + "-" + cat);
	if(checkDiv.length === 0) {
		var title = jQuery("#" + cat).text();
		myButton.attr({value:"-"});
		var parentDiv = jQuery("#pol-" + demo + "-" + vs + "-bargraphs");
		var myDiv = jQuery("<div></div>").attr({id:"pol-" + demo + "-" + vs + "-" + cat + "-bargraphs",class:"bargraphs"});
		var myLabel = jQuery("<h3></h3>").attr({class:"pol-label"}).text(title);
		myDiv.append(myLabel);
		var table1 = jQuery("<table></table>").attr({class:"opinionnaire-bar"});
		var table1body = jQuery("<tbody></tbody");
		var table1row1 = jQuery("<tr></tr>");
		var table1row1cell1 = jQuery("<td></td>").attr({class:"pcmpc-bar-spacer-side"});
		var table1row1cell2 = jQuery("<td></td>").attr({id:"ao" + vs + "-" + demo + "-" + cat}).html("&nbsp;" + ao + " %");
		var table1row1cell3 = jQuery("<td></td>").attr({class:"pcmpc-bar-spacer-mid"});
		var table1row1cell4 = jQuery("<td></td>").attr({id:"pol" + vs + "-" + demo + "-" + cat,class:"pol-graph"}).html(pol + " %" + "&nbsp;");
		var table1row1cell5 = jQuery("<td></td>").attr({class:"pcmpc-bar-spacer-side"});
		table1row1.append(table1row1cell1,table1row1cell2,table1row1cell3,table1row1cell4,table1row1cell5);
		table1body.append(table1row1);
		table1.append(table1body);
		myDiv.append(table1);
		var table2 = jQuery("<table></table>").attr({class:"pointer"});
		var table2body = jQuery("<tbody></tbody");
		var table2row1 = jQuery("<tr></tr>");
		var table2row1cell1 = jQuery("<td></td>").attr({id:"con" + vs + "-" + demo + "-" + cat});
		var table2row1cell2 = jQuery("<td></td>").attr({class:"pcmpc-arrow-td",id:"arrow-" + vs + "-" + demo + "-" + cat});
		var myInnerDiv = jQuery("<div></div>").attr({class:"pcmpc-arrow"});
		table2row1cell2.html('<div class="pcmpc-arrow"></div>' + con + ' %');
		var table2row1cell3 = jQuery("<td></td>");
		table2row1.append(table2row1cell1,table2row1cell2,table2row1cell3);
		table2body.append(table2row1);
		table2.append(table2body);
		myDiv.append(table2);
		parentDiv.append(myDiv);
		var aow = ao * 5;
		aow = Math.min(471,Math.max(29,aow));
		var conw = (((502 - aow) * 0.01) * con) + (aow + 25);
		conw = Math.max(29,conw);
		if(ao == 0) {
			jQuery("#ao" + vs + "-" + demo + "-" + cat).css({width:aow + "px","box-shadow":"inset 0 0 0 1px #000","text-align":"left",background:"#FFFFFF",color:"black"});
		} else {
			jQuery("#ao" + vs + "-" + demo + "-" + cat).css({width:aow + "px",border:"1px solid black","text-align":"left",background:"#82232F",color:"white"});
		}
		if(pol == 0) {
			jQuery("#pol" + vs + "-" + demo + "-" + cat).css({"box-shadow":"inset 0 0 0 1px #000",background:"#FFFFFF",color:"black"});
		}
		jQuery("#con" + vs + "-" + demo + "-" + cat).css({width:conw+"px"});
	} else {
		myButton.attr({value:"+"});
		checkDiv.remove();
	}
}