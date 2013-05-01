/**
 * This is the question form code.
 */

YUI.add('moodle-qtype_canvas-form', function(Y) {
	// Define a name space to call
	var CSS = {
	},
	SELECTORS = {
			GENERICCANVAS: 'canvas[class="qtype_canvas"]',
			FILEPICKER: '#id_qtype_canvas_image_file',
			DRAWINGRADIUS: '#id_radius',
			CHOOSEFILEBUTTON: 'input[name="qtype_canvas_image_filechoose"]',
	};
	Y.namespace('Moodle.qtype_canvas.form');


	Y.Moodle.qtype_canvas.form = {


			canvasContext: new Array(),
			drawingRadius: new Array(),
			emptyCanvasDataURL: new Array(),
			
			
			
			
			init: function(questionID, drawingRadius) {


				if (typeof questionID != undefined) {
					this.drawingRadius[questionID] = drawingRadius;
					this.emptyCanvasDataURL[questionID] = Y.one(SELECTORS.GENERICCANVAS).getDOMNode().toDataURL();
					this.create_canvas_context(questionID);
				}
				Y.delegate('change',    this.filepicker_change,     Y.config.doc, SELECTORS.FILEPICKER, this);
				Y.delegate('click', this.choose_new_image_file_click, Y.config.doc, SELECTORS.CHOOSEFILEBUTTON, this);
				Y.delegate('mousedown', this.canvas_mousedown,  Y.config.doc, SELECTORS.GENERICCANVAS, this);
				Y.delegate('mouseup',   this.canvas_mouseup,    Y.config.doc, SELECTORS.GENERICCANVAS, this);
				Y.delegate('change', this.drawing_radius_change, Y.config.doc, SELECTORS.DRAWINGRADIUS, this);

	
	},
	
	
	
	choose_new_image_file_click: function(e) {
		if (this.is_canvas_empty(0) == false) {
			if (confirm('You have drawn something onto the canvas. Choosing a new image file will erase this. Are you sure you want to go on?') == false) {
				Y.one('.file-picker.fp-generallayout.yui3-panel-content.repository_upload').one('.yui3-button.yui3-button-close').simulate("click");
			}
		}
	},
	
	
	
	get_drawing_radius: function(questionID) {
		if (questionID == 0) {
			this.drawingRadius[0] = Y.one(SELECTORS.DRAWINGRADIUS).get('selectedIndex')*2+1;
		}
		return this.drawingRadius[questionID]*2+1;
	},
	
	
	
	is_canvas_empty: function(questionID) {
		if (questionID == 0) {
			canvasNode = Y.one(SELECTORS.GENERICCANVAS);
		} else {
			Y.all(SELECTORS.GENERICCANVAS).each(function(node) {
				if (node.ancestor().getAttribute('class') == 'qtype_canvas_id_' + questionID) {
					canvasNode = node;
				}
			}.bind(this));
		}		
		if (this.emptyCanvasDataURL[questionID] != 0 && canvasNode.getDOMNode().toDataURL() != this.emptyCanvasDataURL[questionID]) {
			return false;
		}
		return true;
	},
	filepicker_change: function(e) {
		// The clicked qtype_canvas can be found at e.currentTarget.
		var imgURL = Y.one('#id_qtype_canvas_image_file').ancestor().one('div.filepicker-filelist a').get('href');
		var image = new Image();
		image.src = imgURL;
		image.onload = function () {
			Y.one(SELECTORS.GENERICCANVAS).setStyles({
				backgroundImage: "url('" + imgURL + "')",
				display: 'block'
			});
			Y.one(SELECTORS.GENERICCANVAS).getDOMNode().width = image.width;
			Y.one(SELECTORS.GENERICCANVAS).getDOMNode().height = image.height;
			this.emptyCanvasDataURL[0] = Y.one(SELECTORS.GENERICCANVAS).getDOMNode().toDataURL();
			this.create_canvas_context(0);
		}.bind(this);
	},
	create_canvas_context: function(questionID) {
		if (questionID == 0) {
			canvasNode = Y.one(SELECTORS.GENERICCANVAS);
		} else {
			Y.all(SELECTORS.GENERICCANVAS).each(function(node) {
				if (node.ancestor().getAttribute('class') == 'qtype_canvas_id_' + questionID) {
					canvasNode = node;
				}
			}.bind(this));
		}
		this.canvasContext[questionID] = canvasNode.getDOMNode().getContext('2d');
		this.canvasContext[questionID].lineWidth = this.get_drawing_radius(questionID);
		this.canvasContext[questionID].lineJoin = 'round';
		this.canvasContext[questionID].lineCap = 'round';
		this.canvasContext[questionID].strokeStyle = 'blue';

	},
	drawing_radius_change: function(e) {
		if (this.is_canvas_empty(0) == false) {
			if (confirm('If you change the drawing radius now, I will have to erase the whole canvas. Are you okay with that?') == true) {
				Y.one(SELECTORS.GENERICCANVAS).getDOMNode().width = Y.one(SELECTORS.GENERICCANVAS).getDOMNode().width;
				this.create_canvas_context(0);
			} else {
				Y.one(SELECTORS.DRAWINGRADIUS).set('selectedIndex', (this.drawingRadius-1)/2);
			}
		} else {
			this.create_canvas_context(0);
		}
	},
	canvas_mousedown: function(e) {
		questionID = this.canvas_get_question_id(e.currentTarget);
		this.canvasContext[questionID].beginPath();
		var offset = e.currentTarget.getXY();
		this.canvasContext[questionID].moveTo(e.pageX - offset[0], e.pageY - offset[1]);
		Y.on('mousemove', this.canvas_mousemove, e.currentTarget, this);
	},
	canvas_mousemove: function(e) {
		questionID = this.canvas_get_question_id(e.currentTarget);
		var offset = e.currentTarget.getXY();
		this.canvasContext[questionID].lineTo(e.pageX - offset[0], e.pageY - offset[1]);
		this.canvasContext[questionID].stroke();
	},
	canvas_mouseup: function(e) {
		e.currentTarget.detach('mousemove', this.canvas_mousemove);
		questionID = this.canvas_get_question_id(e.currentTarget);
		Y.one('textarea[name="qtype_canvas_textarea_id_'+questionID+'"]').set('value', e.currentTarget.getDOMNode().toDataURL());
	},
	canvas_get_question_id: function(node) {
		if (node.ancestor().getAttribute('class').indexOf('qtype_canvas_id') == -1) {
			return 0;
		} else {
			return node.ancestor().getAttribute('class').replace(/qtype_canvas_id_/gi, '');
		}
	},
};
}, '@VERSION@', {requires: ['node', 'event'] });
