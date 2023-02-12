var hamburger = document.querySelector(".hamburger");
hamburger.addEventListener("click", function(){
  document.querySelector("body").classList.toggle("active");
})

window.onresize = function(e) {
  if ( this.innerWidth <= 992 ) {
    document.querySelector("body").classList.add("active");
  }else {
    document.querySelector("body").classList.remove("active");
  }
}
window.onload = function(e) {
  if ( this.innerWidth <= 992 ) {
    document.querySelector("body").classList.add("active");
  }else {
    document.querySelector("body").classList.remove("active");
  }
}

const links = document.querySelectorAll('.sidebar ul li a.with-submenu');

links.forEach((link) => {
  link.addEventListener("click",(e) => {
    e.preventDefault();
    e.target.nextElementSibling.classList.toggle("show");
  });
});

$('table').excelTableFilter();