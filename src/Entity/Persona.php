<?php

namespace App\Entity;

use App\Repository\PersonaRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PersonaRepository::class)]
class Persona
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nombre = null;

    #[ORM\Column(length: 255)]
    private ?string $apellido = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $fechaNacimiento = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $observaciones = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $foto = null;

    /**
     * @var Collection<int, Relacion>
     */
    #[ORM\OneToMany(targetEntity: Relacion::class, mappedBy: 'personaOrigen')]
    private Collection $relacionesOrigen;

    /**
     * @var Collection<int, Relacion>
     */
    #[ORM\OneToMany(targetEntity: Relacion::class, mappedBy: 'personaDestino')]
    private Collection $relacionesDestino;

    /**
     * @var Collection<int, FechaRecordatorio>
     */
    #[ORM\OneToMany(targetEntity: FechaRecordatorio::class, mappedBy: 'persona', orphanRemoval: true)]
    private Collection $fechaRecordatorios;

    /**
     * @var Collection<int, Observacion>
     */
    #[ORM\OneToMany(targetEntity: Observacion::class, mappedBy: 'persona', orphanRemoval: true)]
    private Collection $diarioObservaciones;

    public function __construct()
    {
        $this->relacionesOrigen = new ArrayCollection();
        $this->relacionesDestino = new ArrayCollection();
        $this->fechaRecordatorios = new ArrayCollection();
        $this->diarioObservaciones = new ArrayCollection();
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

    public function getApellido(): ?string
    {
        return $this->apellido;
    }

    public function setApellido(string $apellido): static
    {
        $this->apellido = $apellido;

        return $this;
    }

    public function getFechaNacimiento(): ?\DateTimeInterface
    {
        return $this->fechaNacimiento;
    }

    public function setFechaNacimiento(?\DateTimeInterface $fechaNacimiento): static
    {
        $this->fechaNacimiento = $fechaNacimiento;

        return $this;
    }

    public function getObservaciones(): ?string
    {
        return $this->observaciones;
    }

    public function setObservaciones(?string $observaciones): static
    {
        $this->observaciones = $observaciones;

        return $this;
    }

    public function getFoto(): ?string
    {
        return $this->foto;
    }

    public function setFoto(?string $foto): static
    {
        $this->foto = $foto;

        return $this;
    }

    /**
     * @return Collection<int, Relacion>
     */
    public function getRelacionesOrigen(): Collection
    {
        return $this->relacionesOrigen;
    }

    public function addRelacionesOrigen(Relacion $relacionesOrigen): static
    {
        if (!$this->relacionesOrigen->contains($relacionesOrigen)) {
            $this->relacionesOrigen->add($relacionesOrigen);
            $relacionesOrigen->setPersonaOrigen($this);
        }

        return $this;
    }

    public function removeRelacionesOrigen(Relacion $relacionesOrigen): static
    {
        if ($this->relacionesOrigen->removeElement($relacionesOrigen)) {
            // set the owning side to null (unless already changed)
            if ($relacionesOrigen->getPersonaOrigen() === $this) {
                $relacionesOrigen->setPersonaOrigen(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Relacion>
     */
    public function getRelacionesDestino(): Collection
    {
        return $this->relacionesDestino;
    }

    public function addRelacionesDestino(Relacion $relacionesDestino): static
    {
        if (!$this->relacionesDestino->contains($relacionesDestino)) {
            $this->relacionesDestino->add($relacionesDestino);
            $relacionesDestino->setPersonaDestino($this);
        }

        return $this;
    }

    public function removeRelacionesDestino(Relacion $relacionesDestino): static
    {
        if ($this->relacionesDestino->removeElement($relacionesDestino)) {
            // set the owning side to null (unless already changed)
            if ($relacionesDestino->getPersonaDestino() === $this) {
                $relacionesDestino->setPersonaDestino(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, FechaRecordatorio>
     */
    public function getFechaRecordatorios(): Collection
    {
        return $this->fechaRecordatorios;
    }

    public function addFechaRecordatorio(FechaRecordatorio $fechaRecordatorio): static
    {
        if (!$this->fechaRecordatorios->contains($fechaRecordatorio)) {
            $this->fechaRecordatorios->add($fechaRecordatorio);
            $fechaRecordatorio->setPersona($this);
        }

        return $this;
    }

    public function removeFechaRecordatorio(FechaRecordatorio $fechaRecordatorio): static
    {
        if ($this->fechaRecordatorios->removeElement($fechaRecordatorio)) {
            // set the owning side to null (unless already changed)
            if ($fechaRecordatorio->getPersona() === $this) {
                $fechaRecordatorio->setPersona(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Observacion>
     */
    public function getDiarioObservaciones(): Collection
    {
        return $this->diarioObservaciones;
    }

    public function addDiarioObservacione(Observacion $diarioObservacione): static
    {
        if (!$this->diarioObservaciones->contains($diarioObservacione)) {
            $this->diarioObservaciones->add($diarioObservacione);
            $diarioObservacione->setPersona($this);
        }

        return $this;
    }

    public function removeDiarioObservacione(Observacion $diarioObservacione): static
    {
        if ($this->diarioObservaciones->removeElement($diarioObservacione)) {
            // set the owning side to null (unless already changed)
            if ($diarioObservacione->getPersona() === $this) {
                $diarioObservacione->setPersona(null);
            }
        }

        return $this;
    }
}
