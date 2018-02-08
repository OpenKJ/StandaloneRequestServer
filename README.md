# OpenKJ Standalone Request Server
Standalone basic single-venue request server implementation for use with OpenKJ.

Requires php
Can be run under either php's built in web server or under any web server with php support like apache or nginx.

Ignores any API key specified in the OpenKJ.

If you were serving this from a web server as http://10.0.0.1/requestserver, you would configure the server URL in OpenKJ to point to http://10.0.0.1/requestserver/api.php 
