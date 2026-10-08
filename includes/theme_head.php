<script>
(function() {
  try {
    var urlParams = new URLSearchParams(window.location.search);
    var qTheme = urlParams.get('theme');
    if (qTheme === 'light' || qTheme === 'dark') {
      localStorage.setItem('folivo_theme', qTheme);
    }
    var saved = localStorage.getItem('folivo_theme');
    var theme = saved || (window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark');
    document.documentElement.setAttribute('data-theme', theme);
  } catch (e) {
    document.documentElement.setAttribute('data-theme', 'dark');
  }
})();
</script>
