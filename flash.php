//inside controller 
$session = new Session();
$session->getFlashBag()->add("info", "La mission a été modifié");

// inside base.html.twig
$(function() {
{% for message in app.session.flashBag.get('error') %}
                ModalAlert.error('{{ message|escape|replace({"\n": "<br/>"})|raw }}');
            {% endfor %}
            {% for message in app.session.flashBag.get('alert') %}
                ModalAlert.alert('{{ message|escape|replace({"\n": "<br/>"})|raw }}');
            {% endfor %}
            {% for message in app.session.flashBag.get('info') %}
                ModalAlert.info('{{ message|escape|replace({"\n": "<br/>"})|raw }}');
{% endfor %}
}
