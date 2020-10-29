let downloadFileTime;

function expireCookie() {
    document.cookie = 'downloadFile=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/'
}

function getCookie(name) {
    var parts = document.cookie.split(name + "=");
    if (parts.length == 2) return parts.pop().split(";").shift();
}


function resetSpinner() {
    console.log('clear')
    $('#displayable_content').removeSpinner();
    expireCookie();
    clearInterval(downloadFileTime);
}

function export_xls() {

    $('#displayable_content').appendSpinner();
    downloadFileTime = setInterval(() => {
        if (getCookie('downloadFile') == 'success') {
            resetSpinner();
        }
    }, 1000)

    window.location.href = "{{ path('commande_export', {
    'mois': mois,
    'annee': annee,
    'numero': 1
})
}
}
";
}