function ShowCompanies() {
	document.getElementById("dropdownlist").addEventListener("transitionend",
		function() {
			if (document.getElementById("dropdownlist").style.overflow=="hidden" || document.getElementById("dropdownlist").style.overflow=="") {
				document.getElementById("dropdownlist").style.overflow="auto";
			} else {
				document.getElementById("dropdownlist").style.overflow="hidden";
			}
		}
	);
	document.getElementById("dropdownlist").style.transition = "max-height 0.3s";
	if (document.getElementById("dropdownlist").style.maxHeight=="0px" || document.getElementById("dropdownlist").style.maxHeight=="") {
		rect=document.getElementById("CompanySelect").getBoundingClientRect();
		var ViewPortHeight = window.innerHeight;
		var DropDownTop=rect.bottom;
		document.getElementById("dropdownlist").style.left=rect.left+"px";
		document.getElementById("dropdownlist").style.maxHeight=ViewPortHeight-DropDownTop-10+"px";
		document.getElementById("CompanySelect").style.background = "url(\'css/ascending.png\') no-repeat right transparent";
		document.getElementById("CompanySelect").style.backgroundSize = "contain";
	} else {
		document.getElementById("dropdownlist").style.overflow="hidden"
		document.getElementById("dropdownlist").style.maxHeight="0px";
		document.getElementById("CompanySelect").style.background = "url(\'css/descending.png\') no-repeat right transparent";
		document.getElementById("CompanySelect").style.backgroundSize = "contain";
	}
	var optionlogos=document.getElementsByClassName("optionlogo");
	var optionlabels=document.getElementsByClassName("optionlabel");
	optionlogos[0].width = 96;
	optionlogos[0].height = 27;
	for (i = 0; i < optionlogos.length; i++) {
		optionlabels[i].style.left = 56-optionlogos[i].width+"px";
		optionlabels[i].style.top = 5-(optionlogos[i].height/2)+"px";
	}
}
function UpdateSelect() {
	document.getElementById("CompanyNameField").value=this.id;
	document.getElementById("CompanySelect").value=document.getElementById("CompanyNameField").options[document.getElementById("CompanyNameField").selectedIndex].text;
	document.getElementById("dropdownlist").style.maxHeight="0px"
	document.getElementById("CompanySelect").style.background = "url(\'css/descending.png\') no-repeat right transparent";
	document.getElementById("CompanySelect").style.backgroundSize = "contain";
}
function TogglePassword () {
	if (document.getElementById("password").type == "password") {
		document.getElementById("password").type = "text";
		document.getElementById("eye").style.background = "url(\'css/crosseye.png\') no-repeat right transparent";
		document.getElementById("eye").style.backgroundSize = "contain";
	} else {
		document.getElementById("password").type = "password";
		document.getElementById("eye").style.background = "url(\'css/eye.png\') no-repeat right transparent";
		document.getElementById("eye").style.backgroundSize = "contain";
	}
}
function checkMousePos(event) {
	if (document.getElementById("dropdownlist").style.maxHeight!="0px" && document.getElementById("dropdownlist").style.maxHeight!="") {
		rect=document.getElementById("CompanySelect").getBoundingClientRect();
		if ((event.clientX < rect.left || event.clientX > rect.right) || (event.clientY < rect.top || event.clientY > rect.bottom)) {
			ShowCompanies();
		}
	}
}
function ShowSpinner() {
	if(document.getElementById("UserNameEntryField").value!='')
		document.getElementById("waiting_show").style.display="block";
}
document.addEventListener("click", checkMousePos);
document.getElementById("eye").addEventListener("click", TogglePassword);
document.getElementById("CompanySelect").addEventListener("click", ShowCompanies);
var options=document.getElementsByClassName("option");
for (i = 0; i < options.length; i++) {
	document.getElementById(options[i].id).addEventListener("click", UpdateSelect);
}