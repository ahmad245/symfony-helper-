 
 //form 
 public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class'            => 'UfmcpBundle\Entity\Mission',
            'allow_extra_fields' => true,
        ));
    }
 //templete
<input type="hidden" id="upload_token" name="mission[upload_token]" value="{{ date('U').getTimestamp }}" /> 
// controller 
$token=$req->request->get('mission')['upload_token'];