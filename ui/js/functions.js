var navbarDropDownState = 0;  //value determines behaviour of navbarDropDown onclick event
var cartToggleState = 0;
var lastScrollTop = $.cookie('last-scroll-top');
var viewportWidth = window.innerWidth;

if (lastScrollTop) {    //reads scrollbar position cookie
  $(window).scrollTop(lastScrollTop);
  $.removeCookie('last-scroll-top');
}

window.addEventListener('resize', function(event){      //refreshes the window on resize to fix display issues with slider and storefont
  let newViewportWidth = window.innerWidth;
  if(viewportWidth !== newViewportWidth){
	console.log('lets do something here like redirect ..');
	$.cookie('last-scroll-top', $(window).scrollTop());
	let url = window.location.href;
	window.location.href= url;
	}    
});

function navbarDropDown(){      //onlick event

    if(navbarDropDownState == 0){
        $("#navbarDropDown").slideDown();
        navbarDropDownState = 1;
         // DEBUG: console.log("state: 1");
    }else{
        $("#navbarDropDown").slideUp();  // document.getElementById('aboutLinks').style.display = "none";
        navbarDropDownState = 0;
        // DEBUG: console.log("state: 0");
    }
}

function toggleCart(){      //onclick event
    if(cartToggleState == 0){
        $("#sidebar").slideDown();
        cartToggleState= 1;
    }else{
       $("#sidebar").slideUp();
        cartToggleState = 0;
    }
}
