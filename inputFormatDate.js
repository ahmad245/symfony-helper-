function format_number(val, size) {
    size = typeof size != "number" ? 2 : size;

    if (isNaN(val)) {
        val = "";
    } else {
        val = val + "";
    }

    while (val.length < size) val = "0" + val;

    return val;
}
$('input[data-type=date],input[data-type=heure]').focusout(function() {
    var val = $(this).val(),
        input_type = $(this).data('type'),
        sep = input_type == 'date' ? '/' : ':';

    new_val = "";
    for (var i in val) {
        var c = val[i],
            n = parseInt(c);

        if (isNaN(n)) {
            continue;
        }

        new_val += n;

        if (new_val.length == 2 || input_type == 'date' && new_val.length == 5) {
            new_val += sep;
        }
    }
    var d_m_y = new_val.split('/');
    d_m_y[0] = d_m_y[0] < 1 ? 1 : d_m_y[0];
    d_m_y[0] = d_m_y[0] > 31 ? 31 : d_m_y[0];
    d_m_y[1] = d_m_y[1] < 1 ? 1 : d_m_y[1];
    d_m_y[1] = d_m_y[1] > 12 ? 12 : d_m_y[1];
    new_val = format_number(d_m_y[0]) + "/" + format_number(d_m_y[1]) + "/" + format_number(d_m_y[2], 4);

    $(this).val(new_val);
});