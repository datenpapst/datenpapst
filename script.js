document.addEventListener('DOMContentLoaded', function() {
  var darkToggle = document.getElementById('dark-toggle');
  if (darkToggle) {
    var dark = localStorage.getItem('dark') === '1';
    darkToggle.checked = dark;
    document.documentElement.classList.toggle('dark', dark);
    darkToggle.addEventListener('change', function() {
      var on = darkToggle.checked;
      document.documentElement.classList.toggle('dark', on);
      localStorage.setItem('dark', on ? '1' : '0');
    });
  }
  var langToggle = document.getElementById('lang-toggle');
  if (langToggle) {
    langToggle.addEventListener('change', function() {
      var lang = langToggle.checked ? 'en' : 'de';
      window.location = 'language.php?lang=' + lang;
    });
  }

  var burger = document.getElementById('burger');
  if (burger) {
    burger.addEventListener('click', function(){
      document.body.classList.toggle('menu-open');
    });
  }

  var cookieBtn = document.getElementById('cookie-accept');
  if (cookieBtn) {
    cookieBtn.addEventListener('click', function(){
      document.cookie = 'cookie_consent=1;path=/;max-age=' + 60*60*24*365;
      document.getElementById('cookie-banner').style.display='none';
    });
  }
  var widgets = document.querySelectorAll('.widget[data-widget]');
  widgets.forEach(function(el){
    var name = el.getAttribute('data-widget');
    fetch('widget_content.php?widget=' + encodeURIComponent(name))
      .then(function(res){ return res.text(); })
      .then(function(html){ el.innerHTML = html; });
  });
});
