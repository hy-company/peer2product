This demo shows the data flow for requests from hybrix to peer2product.

1) Grab an example request from ./example_requests/quote(2).php (or create one based on the example template)
2) Update quote.php:21 with a JSON example.
3) Run quote.php in your browser to simulate a POST request

Running the quote.php script will create an order in ./example_orders/. It's ID and total after fees 
will be sent back to hybrix to be served to the user. When a user agrees with the subtotal hybrix will
collect additional data required for the transaction. Ideally hybrix would hold this data until after
the transaction has been made and then sends a JSON file to order.php based upon the template found in 
./example_orders/. The data required to be collected about the user can be found in the JSON example template.

4) copy or create JSON data from ./example_requests/
 