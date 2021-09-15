# Server socket with php

Simple php socket [Server] for webSocket HTML5
<br />
## Edit /js
don't forget to edit main.js file with <code>ws://YOUR_DOMAIN:PORT/</code>
<br />
## Edit /main.php

<code>
$socket = new ServerSocket(SAME_DOMAIN_IN_JS, SAME_PORT);
</code>
<br />
## run
open your terminal and go to the folder and type <code>php .\main.php</code>,
<br />you should see in the terminal:<br />
<pre>
  <code>
  ------ Server running DOMAIN:PORT ------
  ----- Waitting for client to connect 1/2 ----
  </code>
</pre>

open your browser http://DOMAIN and the client should connect to the server [Client 1].<br />
open a second tab (or new window browser) and type the same url, the client should connect [Client 2].<br />
<br />
in the browser you should see (Socket is open) in both tabs [Client 1, Client 2].<br />
Start sending and reciving the messages between the clients.<br />
<br />
## important
don't forget to enable the php extenstion [socket].
