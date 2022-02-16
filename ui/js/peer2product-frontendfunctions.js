var navbarAboutDropDownState = "inactive";	//value determines behaviour of navbarAboutDropDown onclick event 
var cartToggleState = 1;

function navbarAboutDropDown(){			//onlick event

    if(navbarAboutDropDownState == "inactive"){
         document.getElementById('aboutLinks').style.display = "block";
         navbarAboutDropDownState = "active";
         // console.log("state: 1");
    }else{
        document.getElementById('aboutLinks').style.display = "none";
        navbarAboutDropDownState = "inactive";
        // console.log("state: 0");
    }

}

function toggleCart(){      //onclick event
    if(cartToggleState == 1){
        document.getElementById('sidebar').style.display = "none";
        cartToggleState= 0;
    }else{
        document.getElementById('sidebar').style.display = "block";
        cartToggleState = 1;
    }

}