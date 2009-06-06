var cp = new ColorPicker("window");
// Runs when a color is clicked
function pickColor(color) {
	field.value = color;
}
var field;
function pick(anchorname,target) {
	field = this.document.forms.cats.elements[target];
	cp.show(anchorname);
}