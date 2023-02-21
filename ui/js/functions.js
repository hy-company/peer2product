var navbarDropDownState = 0;  // value determines behaviour of navbarDropDown onclick event
var cartToggleState = 0;
var lastScrollTop = 0; // TO DEPRECATE: $.cookie('last-scroll-top');
var viewportWidth = window.innerWidth;

if (lastScrollTop) {    // reads scrollbar position cookie
  // TO DEPRECATE:  $(window).scrollTop(lastScrollTop);
  // TO DEPRECATE:  $.removeCookie('last-scroll-top');
}

window.addEventListener('resize', function(event){      // refreshes the window on resize to fix display issues with slider and storefont
  let newViewportWidth = window.innerWidth;
  if(viewportWidth !== newViewportWidth){
	console.log('lets do something here like redirect ..');
	// TO DEPRECATE:  $.cookie('last-scroll-top', $(window).scrollTop());
	let url = window.location.href;
	window.location.href= url;
	}    
});

function toggleCart(state) {      // onclick event
  const DIV_sidebar = document.getElementById('cartContainer');
  if (state || cartToggleState == 0) {
    //DIV_sidebar.style.display = 'block';
    DIV_sidebar.style['max-height'] = '50em';
    cartToggleState= 1;
    setTimeout(
      function() {
        document.getElementById('blinky').className = "blink";
      }, 5000);
    
  } else {
    //DIV_sidebar.style.display = 'none';
    DIV_sidebar.style['max-height'] = '0em';
    cartToggleState = 0;
    document.getElementById('blinky').className = "";
  }
}

