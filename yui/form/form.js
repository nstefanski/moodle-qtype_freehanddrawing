/**
 * This is the question editing form code.
 */

YUI.add('moodle-qtype_canvas-form', function(Y) {
    // Define a name space to call
    var CSS = {
        },
        SELECTORS = {
            GENERICCANVAS: '.qtype_canvas'
        };
    Y.namespace('Moodle.qtype_canvas.form');
    Y.Moodle.qtype_canvas.form = {
        // These will need to move...
        canvasContext: 0,
        mousePosition: {x: 0, y: 0},
        mouseMoveSub: 0,

        init: function() {
            Y.delegate('change',    this.canvas_change,     Y.config.doc, SELECTORS.GENERICCANVAS, this);
            Y.delegate('mousedown', this.canvas_mousedown,  Y.config.doc, SELECTORS.GENERICCANVAS, this);
            Y.delegate('mouseup',   this.canvas_mouseup,    Y.config.doc, SELECTORS.GENERICCANVAS, this);
        },
        canvas_change: function(e) {
            // TODO: This needs changing to get the image file?
            // The clicked qtype_canvas can be found at e.currentTarget.
            var imgURL = Y.one('#id_qtype_canvas_image_file').ancestor().one('div.filepicker-filelist a').get('href');
            var image = new Image();
            image.src = imgURL;
            image.on('load', function(image, imageurl) {
                this.setStyles({
                    backgroundImage: "url('" + imageurl + "')",
                    width: image.getStyle('width'),
                    height: image.getStyle('height'),
                    display: 'block'
                });
            });
        },
        canvas_mousedown: function(e) {
            // Haven't touched this yet...
            // The clicked qtype_canvas can be found at e.currentTarget.
            // I'm not sure where the bgimage comes from to know how best
            // to write this

            this.canvasContext.beginPath();
            var offset = Y.one('#qtype_canvas_bgimage').getXY();
            this.canvasContext.moveTo(e.pageX - offset[0], e.pageY - offset[1]);
            //this.canvasContext.moveTo(e.layerX, e.layerY);
            this.mouseMoveSub = Y.on('mousemove', function(f) {
                var offset = Y.one('#qtype_canvas_bgimage').getXY();
                this.canvasContext.lineTo(f.pageX - offset[0], f.pageY - offset[1]);
                //this.canvasContext.lineTo(f.layerX, f.layerY);
                this.canvasContext.stroke();
            }, '#qtype_canvas_bgimage', this);
        },
        canvas_mouseup: function(e) {
            // The clicked qtype_canvas can be found at e.currentTarget.
        },
        old: function() {
            // This needs to move to wherever it's needed ;)
            this.canvasContext = Y.one('#qtype_canvas_bgimage').getDOMNode().getContext('2d');

            this.canvasContext.lineWidth = 5;
            this.canvasContext.lineJoin = 'round';
            this.canvasContext.lineCap = 'round';
            this.canvasContext.strokeStyle = 'blue';
        }
    };
}, '@VERSION@', {requires: ['node', 'event'] });
