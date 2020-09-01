//inside base.html.twig 
{{ js_validator_config() }}
{{ init_js_validation() }}
<script type="text/javascript">
            moment.locale('fr');
            $(function() {
                $.each($('form:not(".customJsValidator")'), function(ind, el) {
                    $(el).jsFormValidator({
                        onValidate: function (errors, event) {
                            var ul_errors = $(el).find('ul.form-errors');
                            $.each(ul_errors, function (errInd, errEl) {
                                var inputId = $(errEl).next().attr('id');
                                if ($.inArray(inputId, Object.keys(errors)) < 0) {
                                    $(errEl).remove();
                                }
                            });

                            if (Object.keys(errors).length > 0) {
                                ModalAlert.alert("Les champs en rouge sont obligatoires ou comportent des erreurs.");
                                return false;
                            }
                        },

                        submitForm: function (form) {
                            if ($(form).data('submitted') == true) {
                                return false;
                            } else {
                                $(form).data('submitted', true);
                                $(form).submit();
                            }
                        }
                    });
                });

            {% for message in app.session.flashBag.get('error') %}
                ModalAlert.error('{{ message|escape|replace({"\n": "<br/>"})|raw }}');
            {% endfor %}
            {% for message in app.session.flashBag.get('alert') %}
                ModalAlert.alert('{{ message|escape|replace({"\n": "<br/>"})|raw }}');
            {% endfor %}
            {% for message in app.session.flashBag.get('info') %}
                ModalAlert.info('{{ message|escape|replace({"\n": "<br/>"})|raw }}');
            {% endfor %}
            });