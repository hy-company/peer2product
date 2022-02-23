var navbarAboutDropDownState = 0;	//value determines behaviour of navbarAboutDropDown onclick event 
var cartToggleState = 0;

function navbarAboutDropDown(){			//onlick event

    if(navbarAboutDropDownState == 0){
        $("#aboutLinks").slideDown();
        navbarAboutDropDownState = 1;   
         // console.log("state: 1");
    }else{
        // document.getElementById('aboutLinks').style.display = "none";
        $("#aboutLinks").slideUp();
        navbarAboutDropDownState = 0;
        // console.log("state: 0");
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
