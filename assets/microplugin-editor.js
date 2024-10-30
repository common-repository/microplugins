(function(){

var editor      = ace.edit("micropluginEditor");
var PhpMode     = ace.require("ace/mode/php").Mode;
var selectTheme = document.getElementById("micropluginEditorThemeSelect");
var fontSize    = document.getElementById("micropluginEditorFontSize");
var postContent = document.getElementById("postContent");

editor.session.setMode(new PhpMode());
editor.session.setOption("useWorker", false);
editor.setShowPrintMargin(false);
editor.setValue(postContent.value);
editor.clearSelection();

var php_errors  = document.getElementsByClassName("php-error");
var annotations = [];

for (var i = 0; i < php_errors.length; i++) {

    var error   = php_errors[i];
    var type    = parseInt(error.getAttribute("data-type"));
    var message = error.getAttribute("data-message");
    var file    = error.getAttribute("data-file");
    var line    = parseInt(error.getAttribute("data-line")) - 1;

    var annotation_type = "warning";

    if (type == 1   || // E_ERROR
        type == 4   || // E_PARSE
        type == 16  || // E_CORE_ERROR
        type == 32  || // E_CORE_WARNING
        type == 64  || // E_COMPILE_ERROR
        type == 128    // E_COMPILE_WARNING
        )
    {
        annotation_type = "error";
    }

    annotations.push({
        row : line,
        column : 0,
        text : message,
        type : annotation_type
    });
}

editor.getSession().setAnnotations(annotations);

editor.on("change", function(){
    postContent.value = editor.getValue();
});

function setThemeFromSelect() {
    editor.setTheme(selectTheme.value);
};

function setFontSizeFromSelect() {
    editor.setFontSize(parseInt(fontSize.value));
};

selectTheme.onchange = function() {
    setThemeFromSelect();
};

fontSize.onchange = function() {
    setFontSizeFromSelect();
};

setThemeFromSelect();
setFontSizeFromSelect();

})();