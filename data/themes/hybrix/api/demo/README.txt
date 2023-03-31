This demo shows the data flow for requests from hybrix to peer2product.

1) Grab an example request from ./example_requests/quote(2).php (or create one based on the example template)
2) Update quote.php:21 with a JSON example.
3) Run quote.php in your browser to simulate a POST request

Running the quote.php script will create an order in ./example_orders/. It's ID and total after fees 
will be sent back to hybrix to be served to the user. When a user agrees with the subtotal hybrix will
collect additional data required for the transaction. Ideally hybrix would hold this data until after
the transaction has been made and then sends a JSON file to order.php based upon the template found in 
./example_orders/. The data required to be collected about the user can be found in the JSON example template.

4) copy or edit JSON data from ./example_requests/order_example.JSON
5) update order.php:11 with the example data.
6) run order.php in your browser to simulate a POST request.

An updated order will now be located in ./example_orders/order_id.JSON

Implementation of these scripts will require tweaks to the pathing of the orders and
additional security checks to verify the requests made to the API. Additionaly a script still has to be 
created to confirm the payment has been sent and recieved. This can be done by making calls to hybrix's
blockchain explorer or by having the hybrix node send a POST request to a webhook script which will update
the orders after the payment has been verified. The best way to implent this check will have to be decided
by the team.


 
