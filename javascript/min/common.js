function Trim(d){if(typeof d!="string"){return d}var c=d;var b="";b=c.substring(0,1);while(b==" "){c=c.substring(1,c.length);b=c.substring(0,1)}b=c.substring(c.length-1,c.length);while(b==" "){c=c.substring(0,c.length-1);b=c.substring(c.length-1,c.length)}return c}function GetCookie(f){var c="MANTIS_"+f;var b=document.cookie;b=b.split(";");var d=0;while(d<b.length){var e=b[d];e=e.split("=");if(Trim(e[0])==c){return(e[1])}d++}return -1}function SetCookie(e,d){var b="MANTIS_"+e;var c=new Date();c.setTime(c.getTime()+(365*24*60*60*1000));document.cookie=b+"="+d+"; expires="+c.toUTCString()+";"}var g_collapse_clear=1;function ToggleDiv(b){t_open_div=document.getElementById(b+"_open");t_closed_div=document.getElementById(b+"_closed");t_cookie=GetCookie("collapse_settings");if(1==g_collapse_clear){t_cookie="";g_collapse_clear=0}if(t_open_div.className=="hidden"){t_open_div.className="";t_closed_div.className="hidden";t_cookie=t_cookie+"|"+b+",1"}else{t_closed_div.className="";t_open_div.className="hidden";t_cookie=t_cookie+"|"+b+",0"}SetCookie("collapse_settings",t_cookie)}function checkall(p_formname,p_state){var t_elements=(eval("document."+p_formname+".elements"));for(var i=0;i<t_elements.length;i++){if(t_elements[i].type=="checkbox"){t_elements[i].checked=p_state}}}var a=navigator.userAgent.indexOf("MSIE");var style_display;if(a!=-1){style_display="block"}else{style_display="table-row"}style_display="block";function setDisplay(c,b){if(!document.getElementById(c)){alert("SetDisplay(): id "+c+" is empty")}if(b!=0){document.getElementById(c).style.display=style_display}else{document.getElementById(c).style.display="none"}}function toggleDisplay(b){setDisplay(b,(document.getElementById(b).style.display=="none")?1:0)}function tag_string_append(b){t_tag_separator=document.getElementById("tag_separator").value;t_tag_string=document.getElementById("tag_string");t_tag_select=document.getElementById("tag_select");if(Trim(b)==""){return}if(t_tag_string.value!=""){t_tag_string.value=t_tag_string.value+t_tag_separator+b}else{t_tag_string.value=t_tag_string.value+b}t_tag_select.selectedIndex=0};