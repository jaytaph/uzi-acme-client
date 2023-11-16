# Acme client for UZI

Steps:

 - create a new account:

    
    $ php uzi-acme.php account:new --email user@example.org --tos

 - view new account:

    
    $ php uzi-acme.php account:view --email user@example.org
    
    Account details for: user@example.org
    +-------------------------------------+
    |        Key: EC/P-256                |
    |    Contact: mailto:user@example.org |
    | Initial IP: 127.0.0.1               |
    | Created At: 2023-11-13T08:34:08Z    |
    |     Status: valid                   |
    +-------------------------------------+

 - create order:
   

    $ php uzi-acme.php order:new --email user@example.org -i jwt:<jwttoken-as-base64>

    A new order has been created.
    +-------------------------------------------------------------+
    |           Location: http://127.0.0.1:4001/acme/order/1/7    |
    |             Status: pending                                 |
    |            Expires: 2023-11-20T10:00:26Z                    |
    | Authorization URLs: Array                                   |
    | (                                                           |
    |     [0] => http://127.0.0.1:4001/acme/authz-v3/7            |
    | )                                                           |
    |                                                             |
    |       Finalize URL: http://127.0.0.1:4001/acme/finalize/1/7 |
    +-------------------------------------------------------------+

 - view authorization details:


    $ php uzi-acme.php auth:view --email user@example.org --url http://127.0.0.1:4001/acme/authz-v3/7

    Array
    (
        [identifier] => Array
            (
                [type] => TsRoXwhqmeM22AXko44NxaUwYfgONTGR5b-yqG4tC4U
                [value] => TsRoXwhqmeM22AXko44NxaUwYfgONTGR5b-yqG4tC4U
            ) 
        [status] => pending
        [expires] => 2023-11-20T10:00:26Z
        [challenges] => Array
            (
                [0] => Array
                    (
                        [type] => trusted-jwt-01
                        [status] => pending
                        [url] => http://127.0.0.1:4001/acme/chall-v3/7/hlZeEw
                        [token] => ChuAPeAW8kzrsd5r-5oja8lqONSbX-oZkUe0sKSOSsM
                    ) 
            ) 
    )

 
- Since there is no token or challenge to do for JWT tokens (all info is present in the JWT token to validate), we can simply accept authorization challenge:


    $ php uzi-acme.php auth:accept --email user@example.org --url http://127.0.0.1:4001/acme/chall-v3/7/hlZeEw
