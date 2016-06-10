$(function(){
    $('input.date, input.datetime-local').each(function(i, el) {
        el = $(el);
        el.get(0).type = 'text';
        el.datetimepicker({
            startDate: el.attr('min'),
            endDate: el.attr('max'),
            weekStart: 1,
            startView: el.is('.date') ? 'month' : 'day',
            maxView: el.is('.date') ? 'decade' : 'day',
            minView: el.is('.date') ? 'month' : 'hour',
            format: el.is('.date') ? 'd. m. yyyy' : 'hh:ii', // for seconds support use 'd. m. yyyy - hh:ii:ss'
            autoclose: true
        });
        el.attr('value') && el.datetimepicker('setValue');
    });

});
