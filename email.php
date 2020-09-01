$this->sendEmail($user->getEmail(),$user->getNom(),$user->getPrenom(),$mission,$text);

public  function sendEmail($email,$nom,$prenom,$mission,$text){
        $message = \Swift_Message::newInstance()
            ->setSubject("Focale - Activités rémunérées à la carte")
            ->setFrom($this->getParameter("mailer_sender_mail"), $this->getParameter("mailer_sender_name"))
           // ->setTo($email)
             ->setTo("a.almasri@onlineformapro.com")
            ->setBody(
                $this->renderView(
                    ('mails/mission-notification.html.twig'),
                    [
                        'nom'  =>$nom,
                        'prenom'=>$prenom,
                        'mission'  => $mission->getLibelle(),
                        'text' =>$text
                    ]
                ),
                'text/html'
            )
        ;
        $mailer = $this->get('mailer');
        $mailer->send($message);

        return $mailer;
    }
