var navbarDropDownState = 0;  //value determines behaviour of navbarDropDown onclick event
var cartToggleState = 0;

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
