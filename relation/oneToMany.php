

//inside entreprise 
    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="UfmcpBundle\Entity\Mission", mappedBy="entreprise", cascade={"persist", "remove", "merge"}, orphanRemoval=true)
     */
    private $missions;





    
    /**
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getMissions(): \Doctrine\Common\Collections\Collection
    {
        return $this->missions;
    }


    public function addMissions(Mission $mission): ?self
    {
        if (!$this->missions->contains($mission)) {
            $this->missions[] = $mission;
            $mission->setEntreprise($this);
        }

        return $this;
    }

    public function removeMissions(Mission $mission): ?self
    {
        if ($this->missions->contains($mission)) {
            $this->missions->removeElement($mission);
            // set the owning side to null (unless already changed)
            if ($mission->getEntreprise() === $this) {
                $mission->setEntreprise(null);
            }
        }

        return $this;
    }