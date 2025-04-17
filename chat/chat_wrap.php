<div id="chat_icon_wrap">
  <span class="material-symbols-outlined">message</span>
</div>

<div id="chat_wrap">
  <div id="chat_header">AI CHAT <span class="material-symbols-outlined">close</span></div>
<?php include_once( dirname(__DIR__) . "/chat/index.php") ?>
</div>
<style>
#chat_icon_wrap {
position: fixed;
height: 55px;
width: 55px;
bottom: 50px;
right: 50px;
color: white;
background-color: black;
border-radius: 50px;
box-shadow: 2px 7px 8px rgba(0,0,0,.3);
}

@media (max-width: 600px) {
  #chat_icon_wrap {
    bottom: 10px;
    right: 10px;
  }
}
#chat_header {
  background-color: black;
  color: white;
  height: 40px;
  line-height: 40px;
  padding-left: 10px;
  cursor: pointer;
}

#chat_header .material-symbols-outlined {
  height: 40px;
  line-height: 40px;
  width: 40px;
  font-size: 35px;
  float: right;
}
</style>

<script>

$("#chat_icon_wrap, #chat_header").on("click", () => {
  $("#chat_icon_wrap").toggle()
  $("#chat_wrap").toggle()
})

</script>
