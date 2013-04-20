/**
 * This is the question editing form code.
 */

YUI.add('moodle-qtype_canvas-form', function(Y) {
    // Define a name space to call
    var CSS = {
        },
        SELECTORS = {
            GENERICCANVAS: '.qtype_canvas',
            FILEPICKER: '#id_qtype_canvas_image_file'
        };
    Y.namespace('Moodle.qtype_canvas.form');
    Y.Moodle.qtype_canvas.form = {
        // These will need to move...
        canvasContext: 0,
        mousePosition: {x: 0, y: 0},
        mouseMoveSub: 0,

        init: function() {
            Y.delegate('change',    this.filepicker_change,     Y.config.doc, SELECTORS.FILEPICKER, this);
            Y.delegate('mousedown', this.canvas_mousedown,  Y.config.doc, SELECTORS.GENERICCANVAS, this);
            Y.delegate('mouseup',   this.canvas_mouseup,    Y.config.doc, SELECTORS.GENERICCANVAS, this);

            this.canvasContext = Y.one(SELECTORS.GENERICCANVAS).getDOMNode().getContext('2d');

            this.canvasContext.lineWidth = 5;
            this.canvasContext.lineJoin = 'round';
            this.canvasContext.lineCap = 'round';
            this.canvasContext.strokeStyle = 'blue';
        },
        filepicker_change: function(e) {
            // TODO: This needs changing to get the image file?
            // The clicked qtype_canvas can be found at e.currentTarget.
            var imgURL = Y.one('#id_qtype_canvas_image_file').ancestor().one('div.filepicker-filelist a').get('href');
            var image = new Image();
            image.src = imgURL;
            image.onload = function () {
                Y.one(SELECTORS.GENERICCANVAS).setStyles({
                    backgroundImage: "url('" + imgURL + "')",
                    /*width: image.width,
                    height: image.height,*/
                    display: 'block'
                });
            };
        },
        canvas_mousedown: function(e) {
            // Haven't touched this yet...
            // The clicked qtype_canvas can be found at e.currentTarget.
            // I'm not sure where the bgimage comes from to know how best
            // to write this

            this.canvasContext.beginPath();
            var offset = Y.one(SELECTORS.GENERICCANVAS).getXY();
            this.canvasContext.moveTo(e.pageX - offset[0], e.pageY - offset[1]);
            //this.canvasContext.moveTo(e.layerX, e.layerY);
            this.mouseMoveSub = Y.on('mousemove', function(f) {
                var offset = Y.one(SELECTORS.GENERICCANVAS).getXY();
                this.canvasContext.lineTo(f.pageX - offset[0], f.pageY - offset[1]);
                //this.canvasContext.lineTo(f.layerX, f.layerY);
                this.canvasContext.stroke();
            }, SELECTORS.GENERICCANVAS, this);
        },
        canvas_mouseup: function(e) {
            // The clicked qtype_canvas can be found at e.currentTarget.
            this.mouseMoveSub.detach();
        },
        old: function() {
            // This needs to move to wherever it's needed ;)
        }
    };
}, '@VERSION@', {requires: ['node', 'event'] });
