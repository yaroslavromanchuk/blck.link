(function($) {
    'use strict';
     $(document).ready(function () {
         if($("#fon").val()){
        $(".fon").css('backgroundImage', 'url(http://blck.link'+$("#fon").val()+')');
    }
    });
    
    $('.carousel').carousel({
         interval : false
    });
  $( ".copy-to-clipboard" ).on( "click", function() {
      $(this).addClass("copied");
      setTimeout(function () {
        $( ".copy-to-clipboard" ).removeClass("copied");
        $(".sharing-box.bottom-shadow").removeClass("visible");
      }, 1000);
     var  element = window.location.href;
     var $temp = $("<input>");
     $("body").append($temp);
     $temp.val(element).select();
     document.execCommand("copy");
     $temp.remove();
     
});  
$( ".sharing" ).on( "click", function() {
  $(".sharing-box.bottom-shadow").addClass("visible");
});
$( ".close-sharing" ).on( "click", function() {
  $(".sharing-box.bottom-shadow").removeClass("visible");
});
$( ".video .carousel-item.active .play" ).on( "click", function() {
 addIframe($(".video .carousel-item.active"), $(this).data("video"));
 click_track($(this).data("id"));
});

$( ".servise a" ).on( "click", function() {
   click_track($(this).data("id"), "servise", $(this).data("name"));
});
$( ".footer a" ).on( "click", function() {
   click_track($(this).data("id"), "link", $(this).data("name"));
});

function addIframe(el, url) {
var target = el;
var newFrame = document.createElement("iframe");
newFrame.setAttribute("src", url);
newFrame.setAttribute("width", "375px");
newFrame.setAttribute("height", "210px");
newFrame.setAttribute("frameborder", 0);
newFrame.setAttribute("allow", "autoplay; encrypted-media");
newFrame.setAttribute("allowfullscreen", "allowfullscreen");
target.html(newFrame);
}
function click_track(id, type, name){ 
    $.post('site/ajax',{id: id, method: type, name: name});
}
})(jQuery);
