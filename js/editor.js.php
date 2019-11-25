<?
header('Content-Type: application/javascript');
include('../static/iniclass.php');
$ini = new TIniFileEx('../static/config.ini');
$lang = parse_ini_file('../lang/'.$ini->read("main","language").'.lang');
?>

var bkExtend = function() {
var args = arguments;
if(args.length == 1) args = [this, args[0]];
for(var prop in args[1]) args[0][prop] = args[1][prop];
return args[0];
};
function bkClass() {}
bkClass.prototype.construct = function() {};
bkClass.extend = function(def) {
var classDef = function() {
if(arguments[0] !== bkClass) { return this.construct.apply(this, arguments); }
};
var proto = new this(bkClass);
bkExtend(proto,def);
classDef.prototype = proto;
classDef.extend = this.extend;
return classDef;
};

var bkElement = bkClass.extend({
construct: function(elm,d) {
if(typeof(elm) == "string") {
elm = (d || document).createElement(elm);
}
elm = $BK(elm);
return elm;
},
appendTo: function(elm) {
elm.appendChild(this);
return this;
},
appendBefore: function(elm) {
elm.parentNode.insertBefore(this,elm);
return this;
},
addEvent: function(type, fn) {
bkLib.addEvent(this,type,fn);
return this;
},
setContent: function(c) {
this.innerHTML = c;
return this;
},
pos: function() {
var curleft = curtop = 0;
var o = obj = this;
if(obj.offsetParent) {
do {
curleft += obj.offsetLeft;
curtop += obj.offsetTop;
} while(obj = obj.offsetParent);
}
var b = (!window.opera) ? parseInt(this.getStyle('border-width') || this.style.border) || 0 : 0;
return [curleft+b,curtop+b+this.offsetHeight];
},
noSelect: function() {
bkLib.noSelect(this);
return this;
},
parentTag: function(t) {
var elm = this;
do {
if(elm && elm.nodeName && elm.nodeName.toUpperCase() == t) {
return elm;
}
elm = elm.parentNode;
} while(elm);
return false;
},
hasClass: function(cls) {
return this.className.match(new RegExp('(\\s|^)nicedit-'+cls+'(\\s|$)'));
},
addClass: function(cls) {
if(!this.hasClass(cls)) { this.className += " nicedit-"+cls };
this.className = this.className.replace(/^\s*/,'').replace(/\s*$/,'');
return this;
},
removeClass: function(cls) {
if(this.hasClass(cls)) {
this.className = this.className.replace(new RegExp('(\\s|^)nicedit-'+cls+'(\\s|$)'),' ');
}
return this;
},
setStyle: function(st) {
var elmStyle = this.style;
for(var itm in st) {
switch(itm) {
case 'float':
elmStyle['cssFloat'] = elmStyle['styleFloat'] = st[itm];
break;
case 'opacity':
elmStyle.opacity = st[itm];
elmStyle.filter = "alpha(opacity=" + Math.round(st[itm]*100) + ")";
break;
case 'className':
this.className = st[itm];
break;
default:
elmStyle[itm] = st[itm];
}
}
return this;
},
getStyle: function(cssRule, d) {
var doc = (!d) ? document.defaultView : d;
if(this.nodeType == 1)
return (doc && doc.getComputedStyle) ? doc.getComputedStyle(this, null).getPropertyValue(cssRule) : this.currentStyle[bkLib.camelize(cssRule)];
},
remove: function() {
this.parentNode.removeChild(this);
return this;
},
setAttributes: function(at) {
for(var itm in at) {
this[itm] = at[itm];
}
return this;
}
});

var bkLib = {
isMSIE: (navigator.appVersion.indexOf("MSIE") != -1),
addEvent: function(obj, type, fn) {
(obj.addEventListener) ? obj.addEventListener( type, fn, false ) : obj.attachEvent("on"+type, fn);
},
toArray: function(iterable) {
var length = iterable.length, results = new Array(length);
while(length--) { results[length] = iterable[length] };
return results;
},
noSelect: function(element) {
if(element.setAttribute && element.nodeName.toLowerCase() != 'input' && element.nodeName.toLowerCase() != 'textarea') {
element.setAttribute('unselectable','on');
}
for(var i=0;i<element.childNodes.length;i++) {
bkLib.noSelect(element.childNodes[i]);
}
},
camelize: function(s) {
return s.replace(/\-(.)/g, function(m, l){return l.toUpperCase()});
},
inArray: function(arr,item) {
return (bkLib.search(arr,item) != null);
},
search: function(arr,itm) {
for(var i=0;i<arr.length;i++) {
if(arr[i] == itm)
return i;
}
return null;
},
cancelEvent: function(e) {
e = e || window.event;
if(e.preventDefault && e.stopPropagation) {
e.preventDefault();
e.stopPropagation();
}
return false;
},
domLoad: [],
domLoaded: function() {
if(arguments.callee.done) return;
arguments.callee.done = true;
for(var i=0;i<bkLib.domLoad.length;i++) bkLib.domLoad[i]();
},
onDomLoaded: function(fireThis) {
this.domLoad.push(fireThis);
if(document.addEventListener) {
document.addEventListener("DOMContentLoaded", bkLib.domLoaded, null);
} else if(bkLib.isMSIE) {
document.write("<style>.nicEdit-main p { margin: 0; }</style><scr"+"ipt id=__ie_onload defer " + ((location.protocol == "https:") ? "src='javascript:void(0)'" : "src=//0") + "><\/scr"+"ipt>");
$BK("__ie_onload").onreadystatechange = function() {
if(this.readyState == "complete"){bkLib.domLoaded();}
};
}
window.onload = bkLib.domLoaded;
}
};

function $BK(elm) {
if(typeof(elm) == "string") {
elm = document.getElementById(elm);
}
return (elm && !elm.appendTo) ? bkExtend(elm,bkElement.prototype) : elm;
}

var bkEvent = {
addEvent: function(evType, evFunc) {
if(evFunc) {
this.eventList = this.eventList || {};
this.eventList[evType] = this.eventList[evType] || [];
this.eventList[evType].push(evFunc);
}
return this;
},
fireEvent: function() {
var args = bkLib.toArray(arguments), evType = args.shift();
if(this.eventList && this.eventList[evType]) {
for(var i=0;i<this.eventList[evType].length;i++) {
this.eventList[evType][i].apply(this,args);
}
}
}
};

function __(s) {
return s;
}

Function.prototype.closure = function() {
var __method = this, args = bkLib.toArray(arguments), obj = args.shift();
return function() { if(typeof(bkLib) != 'undefined') { return __method.apply(obj,args.concat(bkLib.toArray(arguments))); } };
}

Function.prototype.closureListener = function() {
var __method = this, args = bkLib.toArray(arguments), object = args.shift();
return function(e) {
e = e || window.event;
if(e.target) { var target = e.target; } else { var target =  e.srcElement };
return __method.apply(object, [e,target].concat(args));
};
}

var nicEditorConfig = bkClass.extend({
buttons: {
'bold': {name: "<?= $lang['bold'] ?>", command: 'Bold', tags: ['B','STRONG'], css: {'font-weight': 'bold'}, key: 'b'},
'italic': {name: "<?= $lang['italic'] ?>", command: 'Italic', tags: ['EM','I'], css: {'font-style': 'italic'}, key: 'i'},
'underline': {name: "<?= $lang['underline'] ?>", command: 'Underline', tags: ['U'], css: {'text-decoration': 'underline'}, key: 'u'},
'left': {name: "<?= $lang['alignleft'] ?>", command: 'justifyleft', noActive: true},
'center': {name: "<?= $lang['center'] ?>", command: 'justifycenter', noActive: true},
'right': {name: "<?= $lang['alignright'] ?>", command: 'justifyright', noActive: true},
'justify': {name: "<?= $lang['justify'] ?>", command: 'justifyfull', noActive: true},
'ol': {name: "<?= $lang['numbering'] ?>", command: 'insertorderedlist', tags: ['OL']},
'ul': {name: "<?= $lang['bullets'] ?>", command: 'insertunorderedlist', tags: ['UL']},
'subscript': {name: "<?= $lang['subscript'] ?>", command: 'subscript', tags: ['SUB']},
'superscript': {name: "<?= $lang['superscript'] ?>", command: 'superscript', tags: ['SUP']},
'strikethrough': {name: "<?= $lang['strikethrough'] ?>", command: 'strikeThrough', css: {'text-decoration': 'line-through'}},
'removeformat': {name: "<?= $lang['removeformat'] ?>", command: 'removeformat', noActive: true},
'indent': {name: "<?= $lang['indent'] ?>", command: 'indent', noActive: true},
'outdent': {name: "<?= $lang['outdent'] ?>", command: 'outdent', noActive: true},
'hr': {name: "<?= $lang['rule'] ?>", command: 'insertHorizontalRule', noActive: true},
'more': {name: "<?= $lang['preview'] ?>", type: 'nicMoreButton'},
'link': {name: "<?= $lang['link'] ?>", type: 'nicLinkButton', tags: ['A']},
'unlink': {name: "<?= $lang['unlink'] ?>", command: 'unlink', noActive: true},
'undo': {name: "<?= $lang['undo'] ?>", command: 'undo', noActive: true},
'redo': {name: "<?= $lang['redo'] ?>", command: 'redo', noActive: true},
'cut': {name: "<?= $lang['cut'] ?>", command: 'cut', noActive: true},
'selectall': {name: "<?= $lang['selectall'] ?>", command: 'selectall', noActive: true},
'forecolor': {name: "<?= $lang['color'] ?>", type: 'nicEditorColorButton', noClose: true},
'bgcolor': {name: "<?= $lang['highlight'] ?>", type: 'nicEditorBgColorButton', noClose: true},
'fontSize': {name: 'Select Font Size', type: 'nicEditorFontSizeSelect', command: 'fontsize'},
'fontFamily': {name: 'Select Font Family', type: 'nicEditorFontFamilySelect', command: 'fontname'},
'fontFormat': {name: 'Select Font Format', type: 'nicEditorFontFormatSelect', command: 'formatBlock'},
'image': {name: "<?= $lang['image'] ?>", type: 'nicImageButton', tags: ['IMG']},
'table': {name: "<?= $lang['table'] ?>", type: 'nicTableButton'},
'code': {name: "<?= $lang['addcode'] ?>", type: 'nicSnippetButton'},
'xhtml': {name: "<?= $lang['source'] ?>", type: 'nicCodeButton'}
},
iconsPath: './images/icons.png',
buttonList: ['bold','italic','underline','strikethrough','strikethrough','subscript','superscript','left','center','right','justify','ol','ul','indent','outdent','fontFamily','fontSize','fontFormat','undo','redo','removeformat','cut','selectall','more','hr','table','code','image','link','unlink','forecolor','bgcolor','xhtml'],
iconList: {"code":1,"selectall":2,"xhtml":3,"bgcolor":4,"forecolor":5,"bold":6,"center":7,"hr":8,"indent":9,"italic":10,"justify":11,"left":12,"ol":13,"outdent":14,"removeformat":15,"right":16,"undo":17,"strikethrough":18,"subscript":19,"superscript":20,"ul":21,"underline":22,"image":23,"link":24,"unlink":25,"close":26,"redo":27,"arrow":28,"cut":29,"table":30,"more":31}
});

var nicEditors = {
editors: [],
allTextAreas: function(nicOptions) {
var textareas = document.getElementsByTagName("textarea");
for(var i=0;i<textareas.length;i++) {
nicEditors.editors.push(new nicEditor(nicOptions).panelInstance(textareas[i]));
}
return nicEditors.editors;
},
findEditor: function(e) {
var editors = nicEditors.editors;
for(var i=0;i<editors.length;i++) {
if(editors[i].instanceById(e)) {
return editors[i];
}
}
}
};

var nicEditor = bkClass.extend({
construct: function(o) {
this.options = new nicEditorConfig();
bkExtend(this.options,o);
this.nicInstances = new Array();

nicEditors.editors.push(this);
bkLib.addEvent(document.body,'mousedown', this.selectCheck.closureListener(this));
bkLib.addEvent(document.body,'keyup', this.selectCheck.closureListener(this));
},
panelInstance: function(e,o) {
e = this.checkReplace($BK(e));
var panelElm = new bkElement('DIV').setStyle({width: '100%'}).appendBefore(e);
this.setPanel(panelElm);
return this.addInstance(e,o);
},
checkReplace: function(e) {
var r = nicEditors.findEditor(e);
if(r) {
r.removeInstance(e);
r.removePanel();
}
return e;
},
addInstance: function(e,o) {
e = this.checkReplace($BK(e));
if(e.contentEditable || !!window.opera) {
var newInstance = new nicEditorInstance(e,o,this);
} else {
var newInstance = new nicEditorIFrameInstance(e,o,this);
}
this.nicInstances.push(newInstance);
return this;
},
removeInstance: function(e) {
e = $BK(e);
var instances = this.nicInstances;
for(var i=0;i<instances.length;i++) {
if(instances[i].e == e) {
instances[i].remove();
this.nicInstances.splice(i,1);
}
}
},
removePanel: function(e) {
if(this.nicPanel) {
this.nicPanel.remove();
this.nicPanel = null;
}
},
instanceById: function(e) {
e = $BK(e);
var instances = this.nicInstances;
for(var i=0;i<instances.length;i++) {
if(instances[i].e == e) {
return instances[i];
}
}
},
setPanel: function(e) {
this.nicPanel = new nicEditorPanel($BK(e),this.options,this);
this.fireEvent('panel',this.nicPanel);
return this;
},
nicCommand: function(cmd,args) {
if(this.selectedInstance) {
this.selectedInstance.nicCommand(cmd,args);
}
},
getIcon: function(iconName,options) {
var icon = this.options.iconList[iconName];
var file = (options.iconFiles) ? options.iconFiles[iconName] : '';
return {backgroundImage: "url('"+((icon) ? this.options.iconsPath : file)+"')", backgroundPosition : ((icon) ? ((icon-1)*-18) : 0)+'px 0px'};
},
selectCheck: function(e,t) {
var found = false;
do{
if(t.className && t.className.indexOf('nicedit') != -1) {
return false;
}
} while(t = t.parentNode);
this.fireEvent('blur',this.selectedInstance,t);
this.lastSelectedInstance = this.selectedInstance;
this.selectedInstance = null;
return false;
}
});
nicEditor = nicEditor.extend(bkEvent);

var nicEditorInstance = bkClass.extend({
isSelected: false,
construct: function(e,options,nicEditor) {
this.ne = nicEditor;
this.elm = this.e = e;
this.options = options || {};
newY = '250';
this.initialHeight = newY-8;

var isTextarea = (e.nodeName.toLowerCase() == "textarea");
if(isTextarea || this.options.hasPanel) {
var ie7s = (bkLib.isMSIE && !((typeof document.body.style.maxHeight != "undefined") && document.compatMode == "CSS1Compat"))
var s = {};
s[(ie7s) ? 'height' : 'maxHeight'] = (this.ne.options.maxHeight) ? this.ne.options.maxHeight+'px' : null;
this.editorContain = new bkElement('DIV').setStyle(s).addClass('area').appendBefore(e);
var editorElm = new bkElement('DIV').addClass('main').appendTo(this.editorContain);

e.setStyle({display: 'none'});

editorElm.innerHTML = e.innerHTML;

if(isTextarea) {
editorElm.setContent(e.value.replace('<!--more-->', '&lt;!--more--&gt;'));

this.copyElm = e;

var f = e.parentTag('FORM');
if(f) { bkLib.addEvent(f, 'submit', this.saveContent.closure(this)); }
}
editorElm.setStyle((ie7s) ? {height: newY+'px'} : {overflow: 'hidden'});
this.elm = editorElm;
}
this.ne.addEvent('blur',this.blur.closure(this));

this.init();
this.blur();
},
init: function() {
this.elm.setAttribute('contentEditable','true');
if(this.getContent() == "") {
this.setContent('');
}
this.instanceDoc = document.defaultView;
this.elm.addEvent('mousedown',this.selected.closureListener(this)).addEvent('keypress',this.keyDown.closureListener(this)).addEvent('focus',this.selected.closure(this)).addEvent('blur',this.blur.closure(this)).addEvent('keyup',this.selected.closure(this));
this.ne.fireEvent('add',this);
},
remove: function() {
this.saveContent();
if(this.copyElm || this.options.hasPanel) {
this.editorContain.remove();
this.e.setStyle({'display': 'block'});
this.ne.removePanel();
}
this.disable();
this.ne.fireEvent('remove',this);
},
disable: function() {
this.elm.setAttribute('contentEditable','false');
},
getSel: function() {
return (window.getSelection) ? window.getSelection() : document.selection;
},
getRng: function() {
var s = this.getSel();
if(!s || s.rangeCount === 0) { return; }
return (s.rangeCount > 0) ? s.getRangeAt(0) : s.createRange && s.createRange() || document.createRange();
},
selRng: function(rng,s) {
if(window.getSelection) {
s.removeAllRanges();
s.addRange(rng);
} else {
rng.select();
}
},
selElm: function() {
var r = this.getRng();
if(!r) { return; }
if(r.startContainer) {
var contain = r.startContainer;
if(r.cloneContents().childNodes.length == 1) {
for(var i=0;i<contain.childNodes.length;i++) {
var rng = contain.childNodes[i].ownerDocument.createRange();
rng.selectNode(contain.childNodes[i]);
if(r.compareBoundaryPoints(Range.START_TO_START,rng) != 1 && r.compareBoundaryPoints(Range.END_TO_END,rng) != -1) {
return $BK(contain.childNodes[i]);
}
}
}
return $BK(contain);
} else {
return $BK((this.getSel().type == "Control") ? r.item(0) : r.parentElement());
}
},
saveRng: function() {
this.savedRange = this.getRng();
this.savedSel = this.getSel();
},
restoreRng: function() {
if(this.savedRange) {
this.selRng(this.savedRange,this.savedSel);
}
},
keyDown: function(e,t) {
if(e.ctrlKey) {
this.ne.fireEvent('key',this,e);
}
},
selected: function(e,t) {
if(!t && !(t = this.selElm)) { t = this.selElm(); }
if(!e.ctrlKey) {
var selInstance = this.ne.selectedInstance;
if(selInstance != this) {
if(selInstance) {
this.ne.fireEvent('blur',selInstance,t);
}
this.ne.selectedInstance = this;
this.ne.fireEvent('focus',selInstance,t);
}
this.ne.fireEvent('selected',selInstance,t);
this.isFocused = true;
this.elm.addClass('selected');
}
return false;
},
blur: function() {
this.isFocused = false;
this.elm.removeClass('selected');
},
saveContent: function() {
if(this.copyElm || this.options.hasPanel) {
this.ne.fireEvent('save',this);
(this.copyElm) ? this.copyElm.value = this.getContent() : this.e.innerHTML = this.getContent();
}
},
getElm: function() {
return this.elm;
},
getContent: function() {
this.content = this.getElm().innerHTML;
this.ne.fireEvent('get',this);
return this.content;
},
setContent: function(e) {
this.content = e;
this.ne.fireEvent('set',this);
this.elm.innerHTML = this.content;
},
nicCommand: function(cmd,args) {
document.execCommand(cmd,false,args);
}
});

var nicEditorIFrameInstance = nicEditorInstance.extend({
savedStyles: [],
init: function() {
var c = this.elm.innerHTML.replace(/^\s+|\s+$/g, '');
this.elm.innerHTML = '';
(!c) ? c = "<br>" : c;
this.initialContent = c;
this.elmFrame = new bkElement('iframe').setAttributes({'src': 'javascript:;', 'frameBorder': 0, 'allowTransparency': 'true', 'scrolling': 'no'}).setStyle({height: '100px', width: '100%'}).addClass('frame').appendTo(this.elm);

if(this.copyElm) { this.elmFrame.setStyle({width: (this.elm.offsetWidth-4)+'px'}); }

var styleList = ['font-size','font-family','font-weight','color'];
for(var itm in styleList) {
this.savedStyles[bkLib.camelize(itm)] = this.elm.getStyle(itm);
}
setTimeout(this.initFrame.closure(this),50);
},
disable: function() {
this.elm.innerHTML = this.getContent();
},
initFrame: function() {
var fd = $BK(this.elmFrame.contentWindow.document);
fd.designMode = "on";
fd.open();
var css = this.ne.options.externalCSS;
fd.write('<html><head>'+((css) ? '<link href="'+css+'" rel="stylesheet">' : '')+'</head><body id="nicEditContent" style="margin: 0 !important; background: transparent !important;">'+this.initialContent+'</body></html>');
fd.close();
this.frameDoc = fd;

this.frameWin = $BK(this.elmFrame.contentWindow);
this.frameContent = $BK(this.frameWin.document.body).setStyle(this.savedStyles);
this.instanceDoc = this.frameWin.document.defaultView;
this.heightUpdate();
this.frameDoc.addEvent('mousedown', this.selected.closureListener(this)).addEvent('keyup', this.heightUpdate.closureListener(this)).addEvent('keydown', this.keyDown.closureListener(this)).addEvent('keyup', this.selected.closure(this));
this.ne.fireEvent('add',this);
},
getElm: function() {
return this.frameContent;
},
setContent: function(c) {
this.content = c;
this.ne.fireEvent('set',this);
this.frameContent.innerHTML = this.content;
this.heightUpdate();
},
getSel: function() {
return (this.frameWin) ? this.frameWin.getSelection() : this.frameDoc.selection;
},
heightUpdate: function() {
this.elmFrame.style.height = Math.max(this.frameContent.offsetHeight,this.initialHeight)+'px';
},
nicCommand: function(cmd,args) {
this.frameDoc.execCommand(cmd,false,args);
setTimeout(this.heightUpdate.closure(this),100);
}
});

var nicEditorPanel = bkClass.extend({
construct: function(e,options,nicEditor) {
this.elm = e;
this.options = options;
this.ne = nicEditor;
this.panelButtons = new Array();
this.buttonList = bkExtend([],this.ne.options.buttonList);
this.panelContain = new bkElement('DIV').addClass('panelcontain');
this.panelElm = new bkElement('DIV').addClass('panel').appendTo(this.panelContain);
this.panelContain.appendTo(e);

var opt = this.ne.options;
var buttons = opt.buttons;
for(var button in buttons) {
this.addButton(button,opt,true);
}
this.reorder();
e.noSelect();
},
addButton: function(buttonName,options,noOrder) {
var button = options.buttons[buttonName];
var type = (button['type']) ? eval('(typeof('+button['type']+') == "undefined") ? null : '+button['type']+';') : nicEditorButton;
var hasButton = bkLib.inArray(this.buttonList,buttonName);
if(type && (hasButton || this.ne.options.fullPanel)) {
this.panelButtons.push(new type(this.panelElm,buttonName,options,this.ne));
if(!hasButton) {
this.buttonList.push(buttonName);
}
}
},
findButton: function(itm) {
for(var i=0;i<this.panelButtons.length;i++) {
if(this.panelButtons[i].name == itm)
return this.panelButtons[i];
}
},
reorder: function() {
var bl = this.buttonList;
for(var i=0;i<bl.length;i++) {
var button = this.findButton(bl[i]);
if(button) {
this.panelElm.appendChild(button.margin);
}
}
},
remove: function() {
this.elm.remove();
}
});
var nicEditorButton = bkClass.extend({
construct: function(e,buttonName,options,nicEditor) {
this.options = options.buttons[buttonName];
this.name = buttonName;
this.ne = nicEditor;
this.elm = e;
this.margin = new bkElement('DIV').setStyle({'float': 'left', margin: '4px'}).appendTo(e);
this.contain = new bkElement('DIV').setAttributes({'title': this.options.name}).setStyle({width: '20px', height: '20px'}).addClass('buttonContain').appendTo(this.margin);
this.border = new bkElement('DIV').addClass('control').appendTo(this.contain);
this.button = new bkElement('DIV').setStyle({width: '18px', height: '18px', overflow: 'hidden', zoom: 1, cursor: 'pointer'}).addClass('button').setStyle(this.ne.getIcon(buttonName,options)).appendTo(this.border);
this.button.addEvent('mouseover', this.hoverOn.closure(this)).addEvent('mouseout',this.hoverOff.closure(this)).addEvent('mousedown',this.mouseClick.closure(this)).noSelect();

if(!window.opera) {
this.button.onmousedown = this.button.onclick = bkLib.cancelEvent;
}

nicEditor.addEvent('selected', this.enable.closure(this)).addEvent('blur', this.disable.closure(this)).addEvent('key',this.key.closure(this));

this.disable();
this.init();
},
init: function() {  },
hide: function() {
this.contain.setStyle({display: 'none'});
},
updateState: function() {
if(this.isDisabled) { this.setBg(); }
else if(this.isHover) { this.setBg('hover'); }
else if(this.isActive) { this.setBg('active'); }
else { this.setBg(); }
},
setBg: function(state) {
switch(state) {
case 'hover':
var stateStyle = {border: '1px solid #bcbcbc', background: '#e5e5e5'};
break;
case 'active':
var stateStyle = {border: '1px solid #bcbcbc', background: '#e5e5e5'};
break;
default:
var stateStyle = {border: '1px solid #eee', background: '#eee'};
}
this.border.setStyle(stateStyle).addClass('button-'+state);
},
checkNodes: function(e) {
var elm = e;
do {
if(this.options.tags && bkLib.inArray(this.options.tags,elm.nodeName)) {
this.activate();
return true;
}
} while((elm = elm.parentNode) && elm.className != "nicedit");
elm = $BK(e);
while(elm.nodeType == 3) {
elm = $BK(elm.parentNode);
}
if(this.options.css) {
for(var itm in this.options.css) {
if(elm.getStyle(itm,this.ne.selectedInstance.instanceDoc) == this.options.css[itm]) {
this.activate();
return true;
}
}
}
this.deactivate();
return false;
},
activate: function() {
if(!this.isDisabled) {
this.isActive = true;
this.updateState();
this.ne.fireEvent('buttonActivate',this);
}
},
deactivate: function() {
this.isActive = false;
this.updateState();
if(!this.isDisabled) {
this.ne.fireEvent('buttonDeactivate',this);
}
},
enable: function(ins,t) {
this.isDisabled = false;
this.contain.setStyle({'opacity': 1}).addClass('buttonEnabled');
this.updateState();
if(t !== document) {
this.checkNodes(t);
}
},
disable: function(ins,t) {
this.isDisabled = true;
this.contain.setStyle({'opacity': 0.6}).removeClass('buttonEnabled');
this.updateState();
},
toggleActive: function() {
(this.isActive) ? this.deactivate() : this.activate();
},
hoverOn: function() {
if(!this.isDisabled) {
this.isHover = true;
this.updateState();
this.ne.fireEvent("buttonOver",this);
}
},
hoverOff: function() {
this.isHover = false;
this.updateState();
this.ne.fireEvent("buttonOut",this);
},
mouseClick: function() {
if(this.options.command) {
this.ne.nicCommand(this.options.command,this.options.commandArgs);
if(!this.options.noActive) {
this.toggleActive();
}
}
this.ne.fireEvent("buttonClick",this);
},
key: function(nicInstance,e) {
if(this.options.key && e.ctrlKey && String.fromCharCode(e.keyCode || e.charCode).toLowerCase() == this.options.key) {
this.mouseClick();
if(e.preventDefault) e.preventDefault();
}
}
});

var nicPaneOptions = { };

var nicEditorPane = bkClass.extend({
construct: function(elm,nicEditor,options,openButton) {
this.ne = nicEditor;
this.elm = elm;
this.pos = elm.pos();

this.contain = new bkElement('div').setStyle({zIndex: '99999', overflow: 'hidden', position: 'absolute', left: this.pos[0]-1+'px', top: this.pos[1]-2+'px'})
this.pane = new bkElement('div').addClass('pane').setStyle(options).appendTo(this.contain);

this.contain.noSelect().appendTo(document.body);

this.position();
this.init();
},
init: function() { },
position: function() {
if(this.ne.nicPanel) {
var panelElm = this.ne.nicPanel.elm;
var panelPos = panelElm.pos();
var newLeft = panelPos[0]+parseInt(panelElm.getStyle('width'))-(parseInt(this.pane.getStyle('width'))+8);
if(newLeft < this.pos[0]) {
this.contain.setStyle({left: newLeft+'px'});
}
}
},
toggle: function() {
this.isVisible = !this.isVisible;
this.contain.setStyle({display: ((this.isVisible) ? 'block' : 'none')});
},
remove: function() {
if(this.contain) {
this.contain.remove();
this.contain = null;
}
},
append: function(c) {
c.appendTo(this.pane);
},
setContent: function(c) {
this.pane.setContent(c);
}
});

var nicEditorAdvancedButton = nicEditorButton.extend({
init: function() {
this.ne.addEvent('selected',this.removePane.closure(this)).addEvent('blur',this.removePane.closure(this));
},

mouseClick: function() {
if(!this.isDisabled) {
if(this.pane && this.pane.pane) {
this.removePane();
} else {
this.pane = new nicEditorPane(this.contain,this.ne,this);
this.addPane();
this.ne.selectedInstance.saveRng();
}
}
},
addForm: function(f,elm) {
this.form = new bkElement('form').addEvent('submit',this.submit.closureListener(this));
this.pane.append(this.form);
this.inputs = {};

for(var itm in f) {
var field = f[itm];
var val = '';
var placeholder = '';
if(elm) {
val = elm.getAttribute(itm);
placeholder = elm.getAttribute(itm);
}
if(!val) {
val = field['value'] || '';
}
if(!placeholder) {
placeholder = field['placeholder'] || '';
}
var type = f[itm].type;

var contain = new bkElement('div').setStyle({overflow: 'hidden', clear: 'both'}).appendTo(this.form);

switch(type) {
case 'text':
this.inputs[itm] = new bkElement('input').setAttributes({id: itm, 'value': val, placeholder: placeholder, 'type': 'text'}).addClass('input').appendTo(contain);
break;
case 'select':
this.inputs[itm] = new bkElement('select').setAttributes({id: itm}).addClass('select').appendTo(contain);
for(var opt in field.options) {
var o = new bkElement('option').setAttributes({value: opt, selected: (opt == val) ? 'selected' : ''}).setContent(field.options[opt]).appendTo(this.inputs[itm]);
}
break;
case 'content':
this.inputs[itm] = new bkElement('textarea').setAttributes({id: itm}).addClass('textarea').appendTo(contain);
this.inputs[itm].value = val;
}

}
new bkElement('input').setAttributes({'type': 'submit', 'value': '<?= $lang['done']; ?>'}).addClass('submit').appendTo(this.form);
this.form.onsubmit = bkLib.cancelEvent;
},
submit: function() { },
findElm: function(tag,attr,val) {
var list = this.ne.selectedInstance.getElm().getElementsByTagName(tag);
for(var i=0;i<list.length;i++) {
if(list[i].getAttribute(attr) == val) {
return $BK(list[i]);
}
}
},
removePane: function() {
if(this.pane) {
this.pane.remove();
this.pane = null;
this.ne.selectedInstance.restoreRng();
}
}
});

var nicEditorSelect = bkClass.extend({
construct: function(e,buttonName,options,nicEditor) {
this.options = options.buttons[buttonName];
this.elm = e;
this.ne = nicEditor;
this.name = buttonName;
this.selOptions = new Array();

this.margin = new bkElement('div').setStyle({'float': 'left', margin: '4px 2px'}).appendTo(this.elm);
this.contain = new bkElement('div').setStyle({width: '90px', height: '20px', cursor: 'pointer', overflow: 'hidden'}).addClass('selectContain').addEvent('click',this.toggle.closure(this)).appendTo(this.margin);
this.items = new bkElement('div').setStyle({overflow: 'hidden', zoom: 1, border: '1px solid #ccc', paddingLeft: '5px', background: '#fff'}).appendTo(this.contain);
this.control = new bkElement('div').setStyle({overflow: 'hidden', 'float': 'right', height: '18px', width: '16px'}).addClass('selectControl').setStyle(this.ne.getIcon('arrow',options)).appendTo(this.items);
this.txt = new bkElement('div').addClass('selecttxt').appendTo(this.items);

if(!window.opera) {
this.contain.onmousedown = this.control.onmousedown = this.txt.onmousedown = bkLib.cancelEvent;
}

this.margin.noSelect();

this.ne.addEvent('selected', this.enable.closure(this)).addEvent('blur', this.disable.closure(this));

this.disable();
this.init();
},
disable: function() {
this.isDisabled = true;
this.close();
this.contain.setStyle({opacity: 0.6});
},
enable: function(t) {
this.isDisabled = false;
this.close();
this.contain.setStyle({opacity: 1});
},
setDisplay: function(txt) {
this.txt.setContent(txt);
},
toggle: function() {
if(!this.isDisabled) {
(this.pane) ? this.close() : this.open();
}
},
open: function() {
this.pane = new nicEditorPane(this.items,this.ne,{width: '88px', padding: '0px'});

for(var i=0;i<this.selOptions.length;i++) {
var opt = this.selOptions[i];
var itmContain = new bkElement('div').setStyle({overflow: 'hidden', width: '88px', textAlign: 'left', overflow: 'hidden', cursor: 'pointer'});
var itm = new bkElement('div').setStyle({padding: '5px 4px'}).setContent(opt[1]).appendTo(itmContain).noSelect();
itm.addEvent('click',this.update.closure(this,opt[0],opt[2])).addEvent('mouseover',this.over.closure(this,itm)).addEvent('mouseout',this.out.closure(this,itm)).setAttributes('id',opt[0]);
this.pane.append(itmContain);
if(!window.opera) {
itm.onmousedown = bkLib.cancelEvent;
}
}
},
close: function() {
if(this.pane) {
this.pane = this.pane.remove();
}
},
over: function(opt) {
opt.setStyle({background: '#e9e9e9'});
},
out: function(opt) {
opt.setStyle({background: '#fff'});
},
add: function(k,v,d) {
this.selOptions.push(new Array(k,v,d));
},
update: function(elm, elmTxt) {
this.ne.nicCommand(this.options.command,elm);
this.setDisplay(elmTxt);
this.close();
}
});

var nicEditorFontSizeSelect = nicEditorSelect.extend({
sel: {1: '1', 2: '2', 3: '3', 4: '4', 5: '5', 6: '6'},
init: function() {
this.setDisplay("<?= $lang['size'] ?>");
for(var itm in this.sel) {
this.add(itm,'<font size="'+itm+'">'+this.sel[itm]+'</font>',this.sel[itm]);
}
}
});

var nicEditorFontFamilySelect = nicEditorSelect.extend({
sel: {'Roboto': 'Roboto', 'arial': 'Arial', 'comic sans ms': 'Comic Sans', 'courier new': 'Courier New', 'georgia': 'Georgia', 'helvetica': 'Helvetica', 'impact': 'Impact', 'times new roman': 'Times', 'trebuchet ms': 'Trebuchet', 'verdana': 'Verdana'},
init: function() {
this.setDisplay("<?= $lang['font'] ?>");
for(var itm in this.sel) {
this.add(itm,'<font face="'+itm+'">'+this.sel[itm]+'</font>','<font face="'+itm+'">'+this.sel[itm]+'</font>');
}
}
});

var nicEditorFontFormatSelect = nicEditorSelect.extend({
sel: {'h1': 'H1', 'h2': 'H2', 'h3': 'H3', 'h4': 'H4', 'h5': 'H5', 'h6': 'H6'},
init: function() {
this.setDisplay("<?= $lang['style'] ?>");
for(itm in this.sel) {
var tag = itm.toUpperCase();
this.add('<'+tag+'>','<'+itm+' style="padding: 0px; margin: 0px;">'+this.sel[itm]+'</'+tag+'>',this.sel[itm]);
}
}
});

var nicLinkButton = nicEditorAdvancedButton.extend({
addPane: function() {
this.ln = this.ne.selectedInstance.selElm().parentTag('A');
this.addForm({
'href': {type: 'text', value: 'http://'},
'title': {type: 'text', placeholder: '<?= $lang['alttext'] ?>'},
'target': {type: 'select', options: {'': '<?= $lang['curwin'] ?>', '_blank': '<?= $lang['newwin'] ?>'}}
},this.ln);
},
submit: function(e) {
u = this.inputs['href'];
var url = u.value;
if(url == "http://" || url == "") {
u.setStyle({'background': '#fbe0e0', 'transition': '1s'});
setTimeout("u.setStyle({'background': '', 'transition': '1s'})", 700);
return false;
}
this.removePane();

if(!this.ln) {
var tmp = 'javascript:nicTemp();';
this.ne.nicCommand("createlink",tmp);
this.ln = this.findElm('A','href',tmp);
if(this.ln.innerHTML == tmp) {
this.ln.innerHTML = this.inputs['title'].value || url;
}
}
if(this.ln) {
var oldTitle = this.ln.title;
if (this.inputs['title'].value != 0) {
this.ln.setAttributes({
href: this.inputs['href'].value,
title: this.inputs['title'].value,
target: this.inputs['target'].options[this.inputs['target'].selectedIndex].value
});
} else {
this.ln.setAttributes({
href: this.inputs['href'].value,
target: this.inputs['target'].options[this.inputs['target'].selectedIndex].value
});
}
if(this.ln.innerHTML == oldTitle) {
this.ln.innerHTML = this.inputs['title'].value || this.inputs['href'].value;
}
}
}
});

var nicEditorColorButton = nicEditorAdvancedButton.extend({
width: '270px',
addPane: function() {
var colorList = {0: '00', 1: '33', 2: '66', 3:'99', 4: 'CC', 5: 'FF'};
var colorItems = new bkElement('DIV').setStyle({width: '270px'});

for(var r in colorList) {
for(var b in colorList) {
for(var g in colorList) {
var colorCode = '#'+colorList[r]+colorList[g]+colorList[b];
var colorSquare = new bkElement('DIV').setStyle({'cursor': 'pointer', 'height': '15px', 'float': 'left'}).appendTo(colorItems);
var colorBorder = new bkElement('DIV').setStyle({border: '2px solid '+colorCode}).appendTo(colorSquare);
var colorInner = new bkElement('DIV').setStyle({background: colorCode, overflow: 'hidden', width: '11px', height: '11px'}).addEvent('click',this.colorSelect.closure(this,colorCode)).addEvent('mouseover',this.on.closure(this,colorBorder)).addEvent('mouseout',this.off.closure(this,colorBorder,colorCode)).appendTo(colorBorder);

if(!window.opera) {
colorSquare.onmousedown = colorInner.onmousedown = bkLib.cancelEvent;
}
}
}
}
this.pane.append(colorItems.noSelect());
},
colorSelect: function(c) {
this.ne.nicCommand('foreColor',c);
this.removePane();
},
on: function(colorBorder) {
colorBorder.setStyle({border: '2px solid #000'});
},
off: function(colorBorder,colorCode) {
colorBorder.setStyle({border: '2px solid '+colorCode});
}
});

var nicEditorBgColorButton = nicEditorColorButton.extend({
width: '270px',
colorSelect: function(c) {
this.ne.nicCommand('hiliteColor',c);
this.removePane();
}
});

var nicImageButton = nicEditorAdvancedButton.extend({
addPane: function() {
this.im = this.ne.selectedInstance.selElm().parentTag('IMG');
this.addForm({
'src': {type: 'text', 'value': 'http://'},
'alt': {type: 'text', placeholder: '<?= $lang['alttext'] ?>'},
'align': {type: 'select', options: {'left': '<?= $lang['alignleft'] ?>', 'right': '<?= $lang['alignright'] ?>'}}
},this.im);
},

submit: function(e) {
var src = this.inputs['src'].value;
if(src == "" || src == "http://") {
s = this.inputs['src'];
s.setStyle({'background': '#fbe0e0', 'transition': '1s'});
setTimeout("s.setStyle({'background': '', 'transition': '1s'})", 700);
return false;
}
this.removePane();

if(!this.im) {
var tmp = 'javascript:nicImTemp();';
this.ne.nicCommand("insertImage",tmp);
this.im = this.findElm('IMG','src',tmp);
}
if(this.im) {
if(this.inputs['alt'].value != 0) {
this.im.setAttributes({
src: this.inputs['src'].value,
alt: this.inputs['alt'].value,
align: this.inputs['align'].value
});
} else {
this.im.setAttributes({
src: this.inputs['src'].value,
align: this.inputs['align'].value
});
}
}
}
});

var nicCodeButton = nicEditorAdvancedButton.extend({
addPane: function() {
this.addForm({
'code': {type: 'content', 'value': this.ne.selectedInstance.getContent()}
});
},
submit: function(e) {
var code = this.inputs['code'].value;
this.ne.selectedInstance.setContent(code);
this.removePane();
}
});

var nicTableButton = nicEditorAdvancedButton.extend({
addPane: function() {
this.addForm({
'rows': {type: 'select', options: {'2': '2 <?= $lang['rows'] ?>', '3': '3 <?= $lang['rows'] ?>', '4': '4 <?= $lang['rows'] ?>', '5': '5 <?= $lang['rowstwo'] ?>', '6': '6 <?= $lang['rowstwo'] ?>', '7': '7 <?= $lang['rowstwo'] ?>', '8': '8 <?= $lang['rowstwo'] ?>', '9': '9 <?= $lang['rowstwo'] ?>',}},
'cols': {type: 'select', options: {'2': '2 <?= $lang['columns'] ?>', '3': '3 <?= $lang['columns'] ?>', '4': '4 <?= $lang['columns'] ?>', '5': '5 <?= $lang['columnstwo'] ?>', '6': '6 <?= $lang['columnstwo'] ?>', '7': '7 <?= $lang['columnstwo'] ?>', '8': '8 <?= $lang['columnstwo'] ?>', '9': '9 <?= $lang['columnstwo'] ?>',}},
'width': {type: 'select', options: {'10%': '10% <?= $lang['tablew'] ?>', '20%': '20% <?= $lang['tablew'] ?>', '30%': '30% <?= $lang['tablew'] ?>', '50%': '50% <?= $lang['tablew'] ?>', '70%': '70% <?= $lang['tablew'] ?>', '80%': '80% <?= $lang['tablew'] ?>', '90%': '90% <?= $lang['tablew'] ?>', '100%': '100% <?= $lang['tablew'] ?>',}},
});
},
submit: function(e) {
var rows = this.inputs['rows'].options[this.inputs['rows'].selectedIndex].value;
var cols = this.inputs['cols'].options[this.inputs['cols'].selectedIndex].value;
var width = this.inputs['width'].options[this.inputs['width'].selectedIndex].value;
var cellw = (1/cols)*100;
var TableCode = '<table cellpadding=1 cellspacing=0 border=1 width="'+ width +'"><thead><tr>';
	
for(var i=1;i<=cols;i++) {
TableCode += '<th width="'+ cellw +'%"><?= $lang['style'] ?></th>';
}

TableCode += '</tr></thead><tbody>';
var alternate = 'even';

for(var j=1;j<=rows;j++) {
TableCode += '<tr>';
for(var i=1;i<=cols;i++) {
TableCode += '<td width="'+ cellw +'%" class="'+ alternate +'"><?= $lang['content'] ?></td>';
}
TableCode += '</tr>';
if(alternate == 'even') {
var alternate = 'odd';
} else {
var alternate = 'even';
}
}

TableCode += '</tbody></table>'; 
this.removePane();
this.ne.nicCommand('insertHTML', TableCode);
}
});

var nicSnippetButton=nicEditorAdvancedButton.extend({
width: '350px',
addPane: function() {
this.addForm({
'code': {type: 'content', 'value': ''}
});
},
submit: function(e) {
var mycode = this.inputs['code'].value;
if(mycode == "") {
c = this.inputs['code'];
c.setStyle({'background': '#fbe0e0', 'transition': '1s'});
setTimeout("c.setStyle({'background': '', 'transition': '1s'})", 700);
return false;
}
this.removePane();
this.ne.nicCommand('insertHTML', '<br><pre class="code"><code>'+mycode+'</code></pre><br>');
}
});

if(window.location.pathname != "/about" && window.location.pathname != "/about.php") {
var nicMoreButton=nicEditorButton.extend({
mouseClick: function() {
var e = document.getElementsByClassName("nicedit-main")[0].textContent;
if(e.indexOf('<!--more-->') == -1) {
this.ne.nicCommand('insertHTML', '&lt;!--more--&gt;');
}
}
});
}

bkLib.onDomLoaded(
function() { new nicEditor().panelInstance('area'); }
);