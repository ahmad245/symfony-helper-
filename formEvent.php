// PRE_SET_DATA
        $builder->addEventListener(
                    FormEvents::PRE_SET_DATA ,
                    function (FormEvent $event) use ($builder) {
                        $mission = $event->getData();
                        $form = $event->getForm();
                    $mission->setEntreprise([$mission->getEntreprise()]);

                    }
                );


        
//PRE_SUBMIT
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) use ($builder) {
                $data = $event->getData();
                $builderData = $builder->getData();

                $dataEntreprise =$data['entreprise'];
              //  $builderData->setEntreprise($dataEntreprise);

                $entreprisRepo = $this->em->getRepository('UfmcpBundle:Entreprise')->findOneBy ([
                    'nom' =>$dataEntreprise[0]['nom']
                ]);

                if(!empty($entreprisRepo)){

                    $entrepris=$entreprisRepo;
                    $entrepris->setNom($dataEntreprise[0]['nom']);
                    $entrepris->setNumSiret($dataEntreprise[0]['numSiret']);
                    $entrepris->setStatutJuridique($dataEntreprise[0]['statutJuridique']);
                    $entrepris->setActivite($dataEntreprise[0]['activite']);
                    $no=$dataEntreprise[0]['nombreSalaries'];
                    $entrepris->setNombreSalaries($no ? $no : null);
                    $entrepris->setConventionCollective($dataEntreprise[0]['conventionCollective']);
                    $entrepris->setDateDebut(new  \DateTime());
                    $entrepris->setDateFin(new  \DateTime());

                    $entrepris->setFacturationNom($data['facturationNom']);
                    $entrepris->setFacturationPrenom($data['facturationPrenom']);
                    $entrepris->setFacturationTelFixe($data['facturationTelFixe']);
                    $entrepris->setFacturationTelPortable($data['facturationTelPortable']);
                    $entrepris->setFacturationEmail($data['facturationEmail']);
                    $entrepris->setFacturationAdresse($data['facturationAdresse']);
                    $entrepris->setFacturationCp($data['facturationCp']);
                    $entrepris->setFacturationCommune($data['facturationCommune']);
                    $entrepris->setFacturationFonction($data['facturationFonction']);
                    $entrepris->setFacturationCivilite($data['facturationCivilite']);
                    $entrepris->setFacturationCommentaire($data['facturationCommentaire']);


                }
                else{
                    $entrepris=new \UfmcpBundle\Entity\Entreprise();
                    $entrepris->setNom($dataEntreprise[0]['nom']);
                    $entrepris->setNumSiret($dataEntreprise[0]['numSiret']);
                    $entrepris->setStatutJuridique($dataEntreprise[0]['statutJuridique']);
                    $entrepris->setActivite($dataEntreprise[0]['activite']);
                    $entrepris->setNombreSalaries($dataEntreprise[0]['nombreSalaries']);
                    $entrepris->setConventionCollective($dataEntreprise[0]['conventionCollective']);
                    $entrepris->setDateDebut(new  \DateTime());
                    $entrepris->setDateFin(new  \DateTime());

                    $entrepris->setFacturationNom($data['facturationNom']);
                    $entrepris->setFacturationPrenom($data['facturationPrenom']);
                    $entrepris->setFacturationTelFixe($data['facturationTelFixe']);
                    $entrepris->setFacturationTelPortable($data['facturationTelPortable']);
                    $entrepris->setFacturationEmail($data['facturationEmail']);
                    $entrepris->setFacturationAdresse($data['facturationAdresse']);
                    $entrepris->setFacturationCp($data['facturationCp']);
                    $entrepris->setFacturationCommune($data['facturationCommune']);
                    $entrepris->setFacturationFonction($data['facturationFonction']);
                    $entrepris->setFacturationCivilite($data['facturationCivilite']);
                    $entrepris->setFacturationCommentaire($data['facturationCommentaire']);
                    $this->em->persist($entrepris);

                }
                if(isset($entrepris)){
                    $data['entreprise']=$entrepris?? null;
                }

               $builderData->setEntreprise( $entrepris);
                $event->setData($data);

                $this->em->flush();

            }
        );