var navbarAboutDropDownState = "inactive";	//value determines behaviour of navbarAboutDropDown onclick event 

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