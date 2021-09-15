function show_msg(msg, color) {
  if (color == undefined || color == null) {
    color = "white";
  }
  var b = document.getElementById("messages");
  var span = document.createElement("span");
  span.style = "display: block; padding: 10px; color: " + color + ";";
  span.textContent = msg;
  b.append(span);
}

if (!('WebSocket' in window)) {
  show_msg("websocket not supported!", "red");
}

var ws = new WebSocket("ws://localhost:8020");

ws.onopen = function() {
  show_msg("Socket is open", "green");
  // window.onbeforeunload = function() {
  //   ws.send("exit");
  //   // ws.close();
  //   return null;
  // };
}

ws.onmessage = function(e) {
  show_msg(e.data, "blue");
}

ws.onerror = function(e) {
  console.log("Error");
}

ws.onclose = function() {
  show_msg("Socket is closed", "red");
}

var btn = document.getElementById("sendButton");
btn.addEventListener("click", function () {
  var msg = document.getElementById("msg_text").value;
  if (msg != "" && msg != null) {
    ws.send(msg);
    show_msg(msg, "#f48224");
    document.getElementById("msg_text").value = "";
  }
});
