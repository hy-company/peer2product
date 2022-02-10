/* 
    Document   : ppt.js
    Created on : 11-mrt-2013, 12:29:37
    Author     : Kelvin Huizing
    Description:
        Javascript for the PPT demo of Pay.nl
*/

$(document).ready(function() {
           
    $("select[name='payment_profile']").change(
        function()
        {
            var iPaymentProfile = $(this).val();
            
            // if payment profile = 10 (iDeal) is selected, 
            // we have to show a list with the banks available 
            if(iPaymentProfile == 10)
            {
                $("div#banks").show();
            }
            else
            {
                $("div#banks").hide();
            }
        });    
});

/** eof **/