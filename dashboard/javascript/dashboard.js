function RefreshDashboard(Target, Element) {
	Target = "dashboard/"+Target;
	var PostData="";
	if (window.XMLHttpRequest) {// code for IE7+, Firefox, Chrome, Opera, Safari
		xmlhttp=new XMLHttpRequest();
	} else {// code for IE6, IE5
		xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
	}
	xmlhttp.onreadystatechange=function() {
		if (xmlhttp.readyState==4 && xmlhttp.status==200) {
			document.getElementById(Element).innerHTML=xmlhttp.responseText;
		}
	}
	xmlhttp.open("POST",Target,false);
	xmlhttp.setRequestHeader("Content-type","application/x-www-form-urlencoded");
	xmlhttp.setRequestHeader("Cache-Control","no-store, no-cache, must-revalidate");
	xmlhttp.setRequestHeader("Pragma","no-cache");
	xmlhttp.send(PostData);
	return false;
}

function RefreshAll() {
	var elements = document.getElementsByClassName("dashboard_cell");
	for (var i=0; i<elements.length; i++) {
		RefreshDashboard(elements[i].getAttribute('name'), elements[i].id)
	}
}

setInterval(RefreshAll, 10000);