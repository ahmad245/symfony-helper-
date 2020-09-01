// controller 
 $formOptions['disabled'] = true;
 $form = $this->createForm(\UfmcpBundle\Form\Mission::class, $mission,$formOptions);



 // templete 
 {% if form.vars.disabled %} style="cursor: default"{% endif %}


 
 {% if not form.vars.disabled %}
                <div class="submit_zone">
                    <label class="btn_valider" for="submit_mission">
                        <span class="icon-checkmark"></span>
                        VALIDER
                    </label>
                    <input type="submit" id="submit_mission" />
                </div>
 {% endif %}