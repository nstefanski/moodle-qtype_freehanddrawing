/**
 * This is the question editing form code.
 */

YUI.add('moodle-qtype_canvas-form', function(Y) {
    // Define a name space to call
    var CSS = {
        },
        SELECTORS = {
            GENERICCANVAS: '.qtype_canvas',
            FILEPICKER: '#id_qtype_canvas_image_file',
            DRAWINGRADIUS: '#id_radius',
            CHOOSEFILEBUTTON: 'input[name="qtype_canvas_image_filechoose"]',
        };
    Y.namespace('Moodle.qtype_canvas.form');
    Y.Moodle.qtype_canvas.form = {
        // These will need to move...
        canvasContext: 0,
        mouseMoveSub: 0,
        drawingRadius: 0,
        emptyCanvasDataURL: 0,
        init: function() {
            Y.delegate('change',    this.filepicker_change,     Y.config.doc, SELECTORS.FILEPICKER, this);
            Y.delegate('mousedown', this.canvas_mousedown,  Y.config.doc, SELECTORS.GENERICCANVAS, this);
            Y.delegate('mouseup',   this.canvas_mouseup,    Y.config.doc, SELECTORS.GENERICCANVAS, this);
            Y.delegate('change', this.drawing_radius_change, Y.config.doc, SELECTORS.DRAWINGRADIUS, this);
            Y.delegate('click', this.choose_new_image_file_click, Y.config.doc, SELECTORS.CHOOSEFILEBUTTON, this);
        },
        choose_new_image_file_click: function(e) {
            if (this.is_canvas_empty() == false) {
                if (confirm('You have drawn something onto the canvas. Choosing a new image file will erase this. Are you sure you want to go on?') == false) {
                    Y.one('.file-picker.fp-generallayout.yui3-panel-content.repository_upload').one('.yui3-button.yui3-button-close').simulate("click");
                }
            }
        },
        get_drawing_radius: function() {
            this.drawingRadius = Y.one(SELECTORS.DRAWINGRADIUS).get('selectedIndex')*2+1;
            return this.drawingRadius;
        },
        is_canvas_empty: function() {
            if (this.emptyCanvasDataURL != 0 && Y.one(SELECTORS.GENERICCANVAS).getDOMNode().toDataURL() != this.emptyCanvasDataURL) {
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
                this.emptyCanvasDataURL = Y.one(SELECTORS.GENERICCANVAS).getDOMNode().toDataURL();
                this.create_canvas_context();
            }.bind(this);
        },
        create_canvas_context: function() {
                this.canvasContext = Y.one(SELECTORS.GENERICCANVAS).getDOMNode().getContext('2d');
                this.canvasContext.lineWidth = this.get_drawing_radius();
                this.canvasContext.lineJoin = 'round';
                this.canvasContext.lineCap = 'round';
                this.canvasContext.strokeStyle = 'blue';
        },
        drawing_radius_change: function(e) {
            if (this.is_canvas_empty() == false) {
                if (confirm('If you change the drawing radius now, I will have to erase the whole canvas. Are you okay with that?') == true) {
                    Y.one(SELECTORS.GENERICCANVAS).getDOMNode().width = Y.one(SELECTORS.GENERICCANVAS).getDOMNode().width;
                    this.create_canvas_context();
                } else {
                    Y.one(SELECTORS.DRAWINGRADIUS).set('selectedIndex', (this.drawingRadius-1)/2);
                }
            } else {
                this.create_canvas_context();
            }
        },
        canvas_mousedown: function(e) {
            // The clicked qtype_canvas can be found at e.currentTarget.
            this.canvasContext.beginPath();
            var offset = Y.one(SELECTORS.GENERICCANVAS).getXY();
            this.canvasContext.moveTo(e.pageX - offset[0], e.pageY - offset[1]);
            this.mouseMoveSub = Y.on('mousemove', function(f) {
                var offset = Y.one(SELECTORS.GENERICCANVAS).getXY();
                this.canvasContext.lineTo(f.pageX - offset[0], f.pageY - offset[1]);
                this.canvasContext.stroke();
            }, SELECTORS.GENERICCANVAS, this);
        },
        canvas_mouseup: function(e) {
            // The clicked qtype_canvas can be found at e.currentTarget.
            this.mouseMoveSub.detach();
            Y.one('#id_qtype_canvas_textarea').set('value', Y.one(SELECTORS.GENERICCANVAS).getDOMNode().toDataURL());
        },
    };
}, '@VERSION@', {requires: ['node', 'event'] });
