window.onscroll = function() {toggletop()};

function toggletop() {
  if (document.body.scrollTop > 20 || document.documentElement.scrollTop > 20) {
    document.getElementById("topbtn").style.display = "block";
  } else {
    document.getElementById("topbtn").style.display = "none";
  }
}

function scrolltop() {
  document.body.scrollTop = 0; // For Safari
  document.documentElement.scrollTop = 0; // For Chrome, Firefox, IE and Opera
}