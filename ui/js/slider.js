$(document).ready(function(){
  var currentPosition = 0;
  var stopped = 0;
  var automove = 1;
  var automovetimer = 7000;
  var slideWidth = $('#slideshow').css('width').replace('px','');
  var slides = $('.slide');
  var numberOfSlides = slides.length;

  // Remove scrollbar in JS
  $('#slidesContainer').css('overflow', 'hidden');

  // Wrap all .slides with #slideInner div
  slides
    .wrapAll('<div id="slideInner"></div>')
    // Float left to display horizontally, readjust .slides width
	.css({
      'float' : 'left',
      'width' : slideWidth
    });

  // Set #slideInner width equal to total width of all slides
  $('#slideInner').css('width', slideWidth * numberOfSlides);

  // Insert controls in the DOM
  $('#slideshow')
    .prepend('<span class="control" id="leftControl">Clicking moves left</span>')
    .append('<span class="control" id="rightControl">Clicking moves right</span>');

  // Hide left arrow control on first load
  manageControls(currentPosition);
  // Hide right arrow control if no slides available
  if(numberOfSlides<1) { $('#rightControl').hide(); }

  // Create event listeners for .controls clicks
  $('.control')
    .bind('click', function(){
	// Stop automatically moving
	stopped = 1;
    // Determine new position
	currentPosition = ($(this).attr('id')=='rightControl') ? currentPosition+1 : currentPosition-1;
	// Hide / show controls
    manageControls(currentPosition);
    // Move slideInner using margin-left
    $('#slideInner').animate({
      'marginLeft' : slideWidth*(-currentPosition)
    });
  });

  window.setInterval(
		function(){
			if(stopped!=1 && numberOfSlides>1) {
				// Determine new position
				currentPosition = automove==1 ? currentPosition+1 : currentPosition-1;
				// Hide / show controls
				manageControls(currentPosition);
				// Move slideInner using margin-left
				$('#slideInner').animate({
				  'marginLeft' : slideWidth*(-currentPosition)
				})
			}
		}
  ,automovetimer);

  // manageControls: Hides and Shows controls depending on currentPosition
  function manageControls(position){
    // Hide left arrow if position is first slide
	if(position==0){ $('#leftControl').hide(); automove=1; } else{ $('#leftControl').show() }
	// Hide right arrow if position is last slide
    if(position==numberOfSlides-1){ $('#rightControl').hide(); automove=-1; } else{ $('#rightControl').show() }
  }	
});
