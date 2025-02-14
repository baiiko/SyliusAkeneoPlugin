<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinTable;
use Sylius\Component\Core\Model\ProductInterface;

/**
 * @ORM\Entity()
 *
 * @ORM\Table("akeneo_product_group")
 */
class ProductGroup implements ProductGroupInterface
{
    /**
     * @ORM\Id()
     *
     * @ORM\GeneratedValue()
     *
     * @ORM\Column(type="integer")
     */
    private ?int $id = null;

    /**
     * @ORM\ManyToOne(targetEntity="Synolia\SyliusAkeneoPlugin\Entity\ProductGroupInterface")
     *
     * @ORM\JoinColumn(referencedColumnName="id", nullable=true)
     */
    private ?ProductGroupInterface $parent = null;

    /** @ORM\Column(type="string", length=255, nullable=false, unique=true) */
    private string $model;

    /** @ORM\Column(type="array") */
    private array $variationAxes = [];

    /** @ORM\Column(type="string") */
    private string $family = '';

    /** @ORM\Column(type="string") */
    private string $familyVariant = '';

    /**
     * @ORM\ManyToMany(targetEntity="Sylius\Component\Core\Model\Product")
     *
     * @JoinTable(name="akeneo_productgroup_product")
     */
    private Collection $products;

    /** @ORM\Column(type="array") */
    private array $associations = [];

    public function __construct()
    {
        $this->products = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getParent(): ?ProductGroupInterface
    {
        return $this->parent;
    }

    public function setParent(?ProductGroupInterface $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    public function setModel(string $model): ProductGroupInterface
    {
        $this->model = $model;

        return $this;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    /**
     * @return array|string[]
     */
    public function getVariationAxes(): array
    {
        return $this->variationAxes;
    }

    public function setVariationAxes(array $variationAxes): ProductGroupInterface
    {
        $this->variationAxes = $variationAxes;

        return $this;
    }

    public function addVariationAxe(string $variationAxe): ProductGroupInterface
    {
        if (\in_array($variationAxe, $this->variationAxes)) {
            return $this;
        }

        $this->variationAxes[] = $variationAxe;

        return $this;
    }

    public function removeVariationAxe(string $variationAxe): ProductGroupInterface
    {
        if (!\in_array($variationAxe, $this->variationAxes)) {
            return $this;
        }

        unset($this->variationAxes[array_search($variationAxe, $this->variationAxes)]);

        return $this;
    }

    /**
     * @return array|string[]
     */
    public function getAssociations(): array
    {
        return $this->associations;
    }

    public function setAssociations(array $associations): ProductGroupInterface
    {
        $this->associations = $associations;

        return $this;
    }

    public function addAssociation(string $association): ProductGroupInterface
    {
        if (\in_array($association, $this->associations)) {
            return $this;
        }

        $this->associations[] = $association;

        return $this;
    }

    public function removeAssociation(string $association): ProductGroupInterface
    {
        if (!\in_array($association, $this->associations)) {
            return $this;
        }

        unset($this->associations[array_search($association, $this->associations)]);

        return $this;
    }

    public function getFamily(): string
    {
        return $this->family;
    }

    public function setFamily(string $family): ProductGroupInterface
    {
        $this->family = $family;

        return $this;
    }

    public function getFamilyVariant(): string
    {
        return $this->familyVariant;
    }

    public function setFamilyVariant(string $familyVariant): ProductGroupInterface
    {
        $this->familyVariant = $familyVariant;

        return $this;
    }

    /**
     * @return Collection|ProductInterface[]
     */
    public function getProducts(): Collection
    {
        return $this->products;
    }

    public function addProduct(ProductInterface $product): ProductGroupInterface
    {
        if (!$this->products->contains($product)) {
            $this->products->add($product);
        }

        return $this;
    }

    public function removeProduct(ProductInterface $product): ProductGroupInterface
    {
        if ($this->products->contains($product)) {
            $this->products->removeElement($product);
        }

        return $this;
    }
}
