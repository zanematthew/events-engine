jQuery( document ).ready(function( $ ){
    function dateTimePicker(){
        $('.datetime-picker-start').datetimepicker({
            hourMin: 7,
            hourMax: 24,
            dateFormat: "yy-mm-dd",
            stepMinute: 10,
            ampm: true,
            onClose: function(dateText, inst) {
                var endDateTextBox = $('.datetime-picker-end');
                if (endDateTextBox.val() != '') {
                    var testStartDate = new Date(dateText);
                    var testEndDate = new Date(endDateTextBox.val());
                    if (testStartDate > testEndDate)
                        endDateTextBox.val(dateText);
                }
                else {
                    endDateTextBox.val(dateText);
                }
            },
            onSelect: function (selectedDateTime){
                var start = $(this).datetimepicker('getDate');
                $('.datetime-picker-end').datetimepicker('option', 'minDate', new Date(start.getTime()));
            }
        });

        $('.datetime-picker-end').datetimepicker({
            hourMin: 7,
            hourMax: 24,
            dateFormat: "yy-mm-dd",
            stepMinute: 10,
            ampm: true,
            onClose: function(dateText, inst) {
                var startDateTextBox = $('.datetime-picker-start');
                if (startDateTextBox.val() != '') {
                    var testStartDate = new Date(startDateTextBox.val());
                    var testEndDate = new Date(dateText);
                    if (testStartDate > testEndDate)
                        startDateTextBox.val(dateText);
                }
                else {
                    startDateTextBox.val(dateText);
                }
            },
            onSelect: function (selectedDateTime){
                var end = $(this).datetimepicker('getDate');
                $('.datetime-picker-start').datetimepicker('option', 'maxDate', new Date(end.getTime()) );
            }
        });
    }

    if ( jQuery().datetimepicker )
        dateTimePicker();

});