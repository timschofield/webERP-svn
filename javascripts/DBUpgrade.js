function ToggleRows() {
	index = this.id.substring(11);
	if (this.className == "expand_icon")  {
		this.className = "collapse_icon"
		document.getElementById("collapsed_row"+index).style.display="table-cell";
	} else {
		this.className = "expand_icon"
		document.getElementById("collapsed_row"+index).style.display="none";
	}
}

var rows=document.getElementsByClassName("expand_icon");
for (i = 0; i < rows.length; i++) {
	document.getElementById(rows[i].id).addEventListener("click", ToggleRows);
}