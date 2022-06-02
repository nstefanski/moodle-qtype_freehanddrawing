// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

var that = this;
var result = {
    componentInit: function() {
        function canvas_get_question_id(node) {
            if (node.parentNode.getAttribute('class').indexOf('qtype_freehanddrawing_id') == -1) {
                return 0;
            } else {
                return node.parentNode.getAttribute('class').replace(/qtype_freehanddrawing_id_/gi, '');
            }
        }
        function getMobileXY(e) {
            // get x & y coordinates, this varies between mouse & touch and by OS
            var x,y;
            if (e.offsetX && e.offsetY) {
                // mouse events
                x = e.offsetX;
                y = e.offsetY;
            } else if (e.layerX && e.layerY) {
                // iOS touch
                x = e.layerX;
                y = e.layerY;
            } else if (e.touches && e.touches.length > 0) {
                if (e.touches[0].offsetX && e.touches.offsetY) {
                    // touch events
                    x = e.touches[0].offsetX;
                    y = e.touches[0].offsetY;
                } else {
                    // chromebook touch -- no good option, really
                    x = e.touches[0].pageX;
                    y = e.touches[0].pageY;
                }
            }
            if (x && y) {
                var c = e.currentTarget;
                x = x * c.width / c.clientWidth;
                y = y * c.height / c.clientHeight;
            }
            return {'x':x,'y':y};
        }
        function drawStart(e, cx) {
            var xy = getMobileXY(e);
            e.preventDefault();
            e.stopPropagation();
            cx.beginPath();
            // create point from single tap
            cx.moveTo(xy.x,xy.y);
            cx.arc(xy.x,xy.y,0.01,0,2 * Math.PI);
            cx.fill();
            cx.stroke();
        }
        function drawMove(e, cx) {
            var xy = getMobileXY(e);
            e.preventDefault();
            e.stopPropagation();
            cx.lineTo(xy.x,xy.y);
            cx.stroke();
        }

        if (!this.question) {
            console.warn('Aborting because of no question received.');
            return that.CoreQuestionHelperProvider.showComponentError(that.onAbort);
        }
        const div = document.createElement('div');
        div.innerHTML = this.question.html;
         // Get question questiontext.
        const questiontext = div.querySelector('.qtext');

        // Replace Moodle's correct/incorrect and feedback classes with our own.
        // Only do this if you want to use the standard classes
        this.CoreQuestionHelperProvider.replaceCorrectnessClasses(div);
        this.CoreQuestionHelperProvider.replaceFeedbackClasses(div);

         // Treat the correct/incorrect icons.
        this.CoreQuestionHelperProvider.treatCorrectnessIcons(div);

 
        if (div.querySelector('.readonly') !== null) {
            this.question.readonly = true;
        }
        if (div.querySelector('.feedback') !== null) {
            this.question.feedback = div.querySelector('.feedback');
            this.question.feedbackHTML = true;
        }
        
         this.question.text = this.CoreDomUtilsProvider.getContentsOfElement(div, '.qtext');

        if (typeof this.question.text == 'undefined') {
            this.logger.warn('Aborting because of an error parsing question.', this.question.name);
            return this.CoreQuestionHelperProvider.showComponentError(this.onAbort);
        }

        // Called by the reference in *.html to 
        // (afterRender)="questionRendered()
        this.questionRendered = function questionRendered() {
            //do stuff that needs the question rendered before it can run.
            
            var textarea = this.componentContainer.querySelector('.qtype_freehanddrawing_textarea');
            var eraser = this.componentContainer.querySelector('.qtype-freehanddrawing-eraser-mobile');
            var eraserTool = this.componentContainer.querySelector('.qtype-freehanddrawing-erasertool-mobile');
            var drawTool = this.componentContainer.querySelector('.qtype-freehanddrawing-drawtool-mobile');
            var canvas = this.componentContainer.querySelector('.qtype_freehanddrawing_canvas');
            var canvasContext = [];
            var questionID = canvas_get_question_id(canvas);
            var lw = 20; // Default just in case
            if (this.question.radius) {
                lw = this.question.radius;
            }
            
            // copied from create_canvas_context
            canvasContext[questionID] = canvas.getContext('2d');
            canvasContext[questionID].lineJoin = 'round';
            canvasContext[questionID].lineCap = 'round';
            canvasContext[questionID].strokeStyle = 'blue';
            canvasContext[questionID].fillStyle = 'blue';
            canvasContext[questionID].lineWidth = lw;
            canvasContext[questionID].globalCompositeOperation = 'source-over';
            
            var drawing = false;
            
            // blankCanvas to check if the canvas is empty at start
            var blankCanvas = document.createElement('canvas');
            blankCanvas.width = canvas.width;
            blankCanvas.height = canvas.height;
            if(textarea && textarea.value !== blankCanvas.toDataURL()){
                var img = new Image();
                img.onload = function(){
                    canvasContext[questionID].drawImage(img,0,0);
                };
                img.src = textarea.value;
            } else if(this.question.status == 'Correct' || this.question.status == 'Correct') {
                // todo - add answer data to be read by mobile app
            }
            
            eraser.addEventListener('click', function(e) {
                if( confirm(eraser.alt) == true ) {
                    canvasContext[questionID].clearRect(0, 0, canvas.width, canvas.height);
                }
            });
            eraserTool.addEventListener('click', function(e) {
                canvasContext[questionID].globalCompositeOperation = 'destination-out';
                drawTool.style.display = 'block';
                eraserTool.style.display = 'none';
            });
            drawTool.addEventListener('click', function(e) {
                canvasContext[questionID].globalCompositeOperation = 'source-over';
                eraserTool.style.display = 'block';
                drawTool.style.display = 'none';
            });
            canvas.addEventListener('mousedown', function(e) {
                drawStart(e, canvasContext[questionID]);
                drawing = true;
            });
            canvas.addEventListener('mousemove', function(e) {
                if(drawing){
                    drawMove(e, canvasContext[questionID]);
                }
            });
            canvas.addEventListener('mouseup', function(e) {
                drawing = false;
                textarea.value = e.currentTarget.toDataURL();
            });
            canvas.addEventListener('mouseout', function(e) {
                drawing = false;
                textarea.value = e.currentTarget.toDataURL();
            });
            canvas.addEventListener('touchstart', function(e) {
                drawStart(e, canvasContext[questionID]);
                drawing = true;
            });
            canvas.addEventListener('touchmove', function(e) {
                if(drawing){
                    drawMove(e, canvasContext[questionID]);
                }
            });
            canvas.addEventListener('touchend', function(e) {
                drawing = false;
                textarea.value = e.currentTarget.toDataURL();
            });
        }

        // Wait for the DOM to be rendered.
        setTimeout(() => {
            //put stuff here that will be pulled from the rendered question
            var initRad = div.querySelector('.qtype_freehanddrawing_initial_radius');
            if (initRad && initRad.value) {
                this.question.radius = initRad.value;
            }
        });
        return true;
    }
};
result;
