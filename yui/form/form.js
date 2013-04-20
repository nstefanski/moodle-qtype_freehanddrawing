/**
 * This is the question editing form code.
 */

YUI.add('moodle-qtype_canvas-form', function(Y) {
        // Your module code goes here.

        // Define a name space to call
        M.qtype_canvas = M.qtype_canvas || {};
        M.qtype_canvas.form = {
            canvasContext: 0,
            mousePosition: {x: 0, y: 0},
            mouseMoveSub: 0,
            init: function() {


                this.canvasContext = Y.one('#qtype_canvas_bgimage').getDOMNode().getContext('2d');

                this.canvasContext.lineWidth = 5;
                this.canvasContext.lineJoin = 'round';
                this.canvasContext.lineCap = 'round';
                this.canvasContext.strokeStyle = 'blue';



                Y.on('change', function(){
                    var imgURL = Y.one('#id_qtype_canvas_image_file').ancestor().one('div.filepicker-filelist a').get('href');
                    var image = new Image();
                    image.src = imgURL;
                    image.onload = function () {
                        Y.one('#qtype_canvas_bgimage').setStyle("backgroundImage", "url('"+imgURL+"')");
                        Y.one('#qtype_canvas_bgimage').setStyle("width", image.width + "px");
                        Y.one('#qtype_canvas_bgimage').setStyle("height", image.height + "px");
                        Y.one('#qtype_canvas_bgimage').setStyle("display", "block");
                    }
                
                }, '#id_qtype_canvas_image_file');

                Y.on('mousedown', function(e) {
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
                }, '#qtype_canvas_bgimage', this);

                Y.on('mouseup', function() { this.mouseMoveSub.detach(); }, '#qtype_canvas_bgimage', this);

            }


        };
}, '@VERSION@', {requires: ['node', 'event'] });
