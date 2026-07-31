$(document).ready(function() {

    $("#name").on('input', function() {
        if($("#name").val().length != 0) {
            $("#send_guess_btn").prop('disabled', false);
            $("#send_guess_btn").removeClass('disabled');
        }
        else {
            $("#send_guess_btn").prop('disabled', true);
            $("#send_guess_btn").addClass('disabled');
        }
    });

    $('#send_guess_btn').click(function() {
        $(".loading-background").show();
        $(".background-fade").show();

        var gender = $('input:radio[name=gender]:checked').val();

        var weight_unit = $('#weight-unit').val();

        var weight_pound = 0;
        var weight_ounce = 0;
        var weight_kg = 0;

        if (weight_unit === 'imperial') {
            weight_pound = $('#weight-pound').val() || 0;
            weight_ounce = $('#weight-ounce').val() || 0;
        } else {
            weight_kg = $('#weight-kg').val() || 0;
        }
        
        $.ajax({
            type: 'POST',
            url : myajax.ajaxurl,
            dateType: 'json',
            data: {
                action: "save_guess",
                name: $('#name').val(),
                gender: gender,
                weight_pound: weight_pound,
                weight_ounce: weight_ounce,
                weight_kg: weight_kg,
                length: $('#length').val(),
                arrival: $('#arrival-datetime').val(),  
            },
            success: function(response) {
                $(".loading-background").hide();
                $(".background-fade").hide();
                
                if(response.toUpperCase().indexOf('ERROR') >= 0) {
                    alert('error!');
                }
                else {
                    $(".form-response-box").show();
                    var colors;
                    if(gender == "girl") {
                        colors = ["f4a6c1", "d45f93"]
                    }
                    else {
                        colors = ["6fa8dc", "2f6fb5"]
                    }
                    confetti({
                        particleCount: 100,
                        spread: 70,
                        origin: { y: 0.6 },
                        colors: colors
                    });
                    $('#name').val("");
                    $('#gender').val("");
                    $('#weight-pound').val("");
                    $('#weight-ounce').val("");
                    $('#weight-kg').val("");
                    $('#length').val("");
                    $('#arrival-datetime').val("");
                }
            },
            error: function(xhr, ajaxOptions, thrownError) {
                alert('error!');
            }
        })
        return false;
    });

    $("#close-modal").click(function() {
        $(".yes-response").hide();
        $(".no-response").hide();
        $(".form-response-box").hide();
    });
})