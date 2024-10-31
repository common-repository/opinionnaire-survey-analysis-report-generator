/*var lastPos = 0;

jQuery("#pr_graphic_top").click(function() {
    location.hash = "";
});

jQuery(window).bind("hashchange",function(event) {
    var hash = location.hash.replace("#","");
    if(hash == "") jQuery(window).scrollTop(lastPos);
    //alert(hash);
});
*/
jQuery(function() {
    // Remove the # from the hash, as different browsers may or may not include it
    var hash = location.hash.replace("#","");
    if(hash != "") {
        // Clear the hash in the URL
        location.hash = "";
    }
});