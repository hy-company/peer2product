var navbarAboutDropDownState = "inactive";	//value determines behaviour of navbarAboutDropDown onclick event 
var cartToggleState = 0;

function navbarAboutDropDown(){			//onlick event

    if(navbarAboutDropDownState == "inactive"){
        $("#aboutLinks").fadeIn(); 
        navbarAboutDropDownState = "active";
         
           
             
            
         
          
          
          
          
         // console.log("state: 1");
    }else{
        // document.getElementById('aboutLinks').style.display = "none";
        $("#aboutLinks").fadeOut();
        navbarAboutDropDownState = "inactive";
        // console.log("state: 0");
    }

}

function toggleCart(){      //onclick event
    if(cartToggleState == 0){
        $("#sidebar").fadeIn();
        cartToggleState= 1;
    }else{
       $("#sidebar").fadeOut();
        cartToggleState = 0;
    }

}