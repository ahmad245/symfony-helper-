// date 
->add("dateDebut",null, [
                "label" => "Date de début",
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'html5' => false,
                'required' => false,

                'attr' => ['class' => 'input-date']
            ])
            ->add("dateFin",null, [
                "label" => "Date de fin",
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'html5' => false,
                'required' => false,

                'attr' => ['class' => 'input-date']
])


// ChoiceType 
->add('facturationCivilite', ChoiceType::class, [
                'label' => 'Civilité',
                'expanded' => true,
                'required' => true,
                'choices' => [
                    'Monsieur' => \UfmcpBundle\Entity\Mission::CIVILITE_M,
                    'Madame' => \UfmcpBundle\Entity\Mission::CIVILITE_MME,
                ]
            ])

// collection
->add('entreprise',CollectionType::class, [
                'entry_type'=>Entreprise::class,
                'allow_add'=>true,
                'allow_delete'=>true,
                'label'=>false
            ])            