
  //user  ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////
    /**
     * @ORM\ManyToMany(targetEntity="UfmcpBundle\Entity\Mission" , inversedBy="interessePar")
     * @ORM\JoinTable(name="territoire_base.mission_interesse",
     *                joinColumns={@ORM\JoinColumn(name="id_stagiaire",referencedColumnName="id")},
     *                inverseJoinColumns={@ORM\JoinColumn(name="id_mission",referencedColumnName="id")}
     * )
     */
    private $missionInteresses;

    public function getMissionInteresses()
    {
        return $this->missionInteresses;
    }

    /**
     * @param mixed $missionInteresses
     */
    public function setMissionInteresses($missionInteresses): void
    {
        $this->missionInteresses = $missionInteresses;
    }
    /**
     *
     * @param Mission $mission
     * @return self
     */
    public function addMissionInteresses(Mission $mission):self
    {
        if (!$this->missionInteresses->contains($mission)) {
            $this->missionInteresses[] = $mission;
            $mission->addInteressePar($this);
        }

        return $this;
    }

    /**
     * @param Mission $mission
     * @return self
     */
    public function removeMissionInteresses(Mission $mission):self
    {
        if ($this->missionInteresses->contains($mission)) {
            $this->missionInteresses->removeElement($mission);
            $mission->removeInteressePar($this);
        }

        return $this;
    }
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


// mission 
/**
     * @ORM\ManyToMany(targetEntity="UfmcpBundle\Entity\Stagiaire",mappedBy="missionInteresses", fetch="LAZY")

     *
 */
    private $interessePar;    



    /**
     * @return mixed
     */
    public function getInteressePar()
    {
        return $this->interessePar;
    }

    /**
     * @param mixed $interessePar
     */
    public function setInteressePar($interessePar): void
    {
        $this->interessePar = $interessePar;
    }

    public function siInteressePar(Stagiaire $stagiaire){

        if ($this->interessePar->contains($stagiaire)) return true;
        return false;
    }
    /**
     * Undocumented function
     *
     * @param Stagiaire $stagiaire
     * @return self
     */
    public function addInteressePar(Stagiaire $stagiaire):self
    {
        if (!$this->siInteressePar($stagiaire)) {
            $this->interessePar[]=$stagiaire;
            $stagiaire->addMissionInteresses($this);
        }
        return $this;

    }
    /**
     * Undocumented function
     *
     * @param User $user
     * @return self
     */
    public function removeInteressePar(Stagiaire $stagiaire):self
    {
        if ($this->siInteressePar($stagiaire)) {
            $this->interessePar->removeElement($stagiaire);
            $stagiaire->removeMissionInteresses($this);
        }
        return $this;
    }




 // inside controller //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
 $siInteresse=$mission->siInteressePar($user);
 $mission->addInteressePar($user);
 $mission->removeInteressePar($user);   