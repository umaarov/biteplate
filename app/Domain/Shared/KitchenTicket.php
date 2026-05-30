<?php

declare(strict_types=1);

namespace App\Domain\Shared;

/**
 * A single line the kitchen must act on.
 *
 * A plain leaf dish yields one ticket; a {@see \App\Domain\Menu\ComboMeal}
 * (Composite) flattens to many; a {@see \App\Domain\Menu\Customization\MenuItemDecorator}
 * appends preparation notes and allergen flags onto the ticket it wraps.
 *
 * @param list<string>   $notes      Free-text prep instructions ("no nuts", "extra cheese").
 * @param list<Allergen> $allergens  Allergens present after customisation.
 */
final readonly class KitchenTicket
{
    public function __construct(
        public string $item,
        public KitchenStation $station,
        public array $notes = [],
        public array $allergens = [],
    ) {
    }

    public function withNote(string $note): self
    {
        return new self($this->item, $this->station, [...$this->notes, $note], $this->allergens);
    }

    /** @param list<Allergen> $allergens */
    public function withAllergens(array $allergens): self
    {
        return new self(
            $this->item,
            $this->station,
            $this->notes,
            array_values(array_unique([...$this->allergens, ...$allergens], SORT_REGULAR)),
        );
    }
}
