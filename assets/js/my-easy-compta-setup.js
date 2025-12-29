jQuery(document).ready(function ($) {
  showStep(1);
  const notyf = new Notyf();

  $(".wcpa-ui-toggle").change(function () {
    if (this.checked) {
      $("#dafult_vat_div").show();
    } else {
      $("#dafult_vat_div").hide();
    }
  });

  $("#my-easy-compta-setup-content").on("submit", "form", function (e) {
    e.preventDefault();
    var form = $(this);
    var formData = form.serialize();
    var step = form.data("step");
    var ajaxAction = "ecwp_handle_setup_step" + step;
    var submitButton = form.find('button[type="submit"]');
    var loadingSpinner = submitButton.find(".loading");

    submitButton.prop("disabled", true);
    loadingSpinner.addClass("active");

    $.ajax({
      type: "POST",
      url: ecwp_ajax.ajax_url,
      data:
        formData +
        "&action=" +
        ajaxAction +
        "&security=" +
        ecwp_ajax.ecwp_setup_nonce,
      dataType: "json",
      success: function (response) {
        submitButton.prop("disabled", false);
        loadingSpinner.removeClass("active");

        if (response.success) {
          showStep(step + 1);
          notyf.success(response.data.message);
        } else {
          notyf.error(response.data.message);
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        submitButton.prop("disabled", false);
        loadingSpinner.removeClass("active");

        alert("Error: " + errorThrown);
        notyf.error(errorThrown);
      },
    });
  });

  $("#skip-step3").click(function () {
    showStep(4);
  });

  function showStep(step) {
    $(".step").removeClass("active");
    $(".step" + step).addClass("active");
    $(".setup-step").hide();
    $("#step" + step).show();
  }
});
