(function ($) {
  $(document).ready(function () {
    $(".my-easy-compta-banner-close").on("click", function () {
      var banner = $(this).closest(".my-easy-compta-banner-container");
      banner.fadeOut("slow", function () {
        document.cookie = "my_easy_compta_banner_closed=1; path=/";
      });
    });

    $(".my-easy-compta-banner-never-show").on("click", function () {
      var banner = $(this).closest(".my-easy-compta-banner-container");
      banner.fadeOut("slow", function () {
        $.post(ajaxurl, {
          action: "my_easy_compta_admin_notification_hide",
          never_show: true,
        });
      });
    });
  });

  function getCookie(name) {
    var value = "; " + document.cookie;
    var parts = value.split("; " + name + "=");
    if (parts.length === 2) return parts.pop().split(";").shift();
  }

  if (getCookie("my_easy_compta_banner_closed")) {
    $(".my-easy-compta-banner-banner").hide();
  }
})(jQuery);
