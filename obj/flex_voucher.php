<?php
 class OrderDetails{
   
    function __construct($payload){
        $payload = json_decode($payload);
        $this->order_id = uniqid();
        $this->firstname = $payload->firstname;
        $this->lastname = $payload->lastname;
        $this->company = "hybrix";
        $this->time = time();
        $this->transport = 0;
        $this->type = $payload->type;
        $this->amount = $payload->amount;
        $this->{"e-mail"} = $payload->{"e-mail"};
        $this->streetname = $payload->target;
        $this->remarks = "bank account details";
        // $this->product_table = "<div>ORDER TYPE: {deposit}<br> TO: {target adress}<br> AMOUNT: {amount}<br> NAME: {BANK ACC OWNER NAME}</div>";
        
    }
}
?>