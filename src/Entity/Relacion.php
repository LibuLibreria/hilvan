<?php

namespace App\Entity;

use App\Repository\RelacionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RelacionRepository::class)]
class Relacion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'relacionesOrigen')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Persona $personaOrigen = null;

    #[ORM\ManyToOne(inversedBy: 'relacionesDestino')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Persona $personaDestino = null;

    #[ORM\ManyToOne(inversedBy: 'relaciones')]
    #[ORM\JoinColumn(nullable: false)]
    private ?TipoRelacion $tipoRelacion = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPersonaOrigen(): ?Persona
    {
        return $this->personaOrigen;
    }

    public function setPersonaOrigen(?Persona $personaOrigen): static
    {
        $this->personaOrigen = $personaOrigen;

        return $this;
    }

    public function getPersonaDestino(): ?Persona
    {
        return $this->personaDestino;
    }

    public function setPersonaDestino(?Persona $personaDestino): static
    {
        $this->personaDestino = $personaDestino;

        return $this;
    }

    public function getTipoRelacion(): ?TipoRelacion
    {
        return $this->tipoRelacion;
    }

    public function setTipoRelacion(?TipoRelacion $tipoRelacion): static
    {
        $this->tipoRelacion = $tipoRelacion;

        return $this;
    }
}
