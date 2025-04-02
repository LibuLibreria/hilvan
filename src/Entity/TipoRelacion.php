<?php

namespace App\Entity;

use App\Repository\TipoRelacionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TipoRelacionRepository::class)]
class TipoRelacion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nombre = null;

    #[ORM\Column(length: 255)]
    private ?string $nombreInverso = null;

    /**
     * @var Collection<int, Relacion>
     */
    #[ORM\OneToMany(targetEntity: Relacion::class, mappedBy: 'tipoRelacion')]
    private Collection $relaciones;

    public function __construct()
    {
        $this->relaciones = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNombre(): ?string
    {
        return $this->nombre;
    }

    public function setNombre(string $nombre): static
    {
        $this->nombre = $nombre;

        return $this;
    }

    public function getNombreInverso(): ?string
    {
        return $this->nombreInverso;
    }

    public function setNombreInverso(string $nombreInverso): static
    {
        $this->nombreInverso = $nombreInverso;

        return $this;
    }

    /**
     * @return Collection<int, Relacion>
     */
    public function getRelaciones(): Collection
    {
        return $this->relaciones;
    }

    public function addRelacione(Relacion $relacione): static
    {
        if (!$this->relaciones->contains($relacione)) {
            $this->relaciones->add($relacione);
            $relacione->setTipoRelacion($this);
        }

        return $this;
    }

    public function removeRelacione(Relacion $relacione): static
    {
        if ($this->relaciones->removeElement($relacione)) {
            // set the owning side to null (unless already changed)
            if ($relacione->getTipoRelacion() === $this) {
                $relacione->setTipoRelacion(null);
            }
        }

        return $this;
    }
}
