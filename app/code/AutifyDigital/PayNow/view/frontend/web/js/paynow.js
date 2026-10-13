require(['jquery', 'jquery/ui'], function($){ 
    $(document).ready(function() {
        $("#enable_shipping").on("change", function () {
            if (this.checked) {
                $(".shipping-address-container").hide();
                $("[name='shipto_name']").val($("[name='billto_name']").val());
                $("[name='shipto_street1']").val($("[name='billto_street1']").val());
                $("[name='shipto_street2']").val($("[name='billto_street2']").val());
                $("[name='shipto_city']").val($("[name='billto_city']").val());
                $("[name='shipto_postcode']").val($("[name='billto_postcode']").val());
                $("[name='shipto_state']").val($("[name='billto_state']").val());
                $("[name='shipto_country']").val($("[name='billto_country']").val());
                $('[name="shipto_phone"]').val($("[name='billto_phone']").val());

                $('[name="billto_name"]').change(function () {
                    $('[name="shipto_name"]').val($(this).val());
                });

                $('[name="billto_street1"]').change(function () {
                    $('[name="shipto_street1"]').val($(this).val());
                });

                $('[name="billto_street2"]').change(function () {
                    $('[name="shipto_street2"]').val($(this).val());
                });

                $('[name="billto_city"]').change(function () {
                    $('[name="shipto_city"]').val($(this).val());
                });

                $('[name="billto_postcode"]').change(function () {
                    $('[name="shipto_postcode"]').val($(this).val());
                });

                $('[name="billto_state"]').change(function () {
                    $('[name="shipto_state"]').val($(this).val());
                });

                $('[name="billto_phone"]').change(function () {
                    $('[name="shipto_phone"]').val($(this).val());
                });

                $("#billto_country").change(function () {
                    var billingCountryVal = $("option:selected", this).attr("value");
                    $("#shipto_country").val(billingCountryVal);
                });
            } else {
                $(".shipping-address-container").show();
                $("[name='shipto_name']").val("");
                $("[name='shipto_street1']").val("");
                $("[name='shipto_street2']").val("");
                $("[name='shipto_city']").val("");
                $("[name='shipto_postcode']").val("");
                $("[name='shipto_state']").val("");
                $("[name='shipto_country']").val("GB");
                $('[name="shipto_phone"]').val("");
            }
        });
    });
    
});

