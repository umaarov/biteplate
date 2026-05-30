<?php

declare(strict_types=1);

namespace App\Domain\Menu\Factory;

use App\Domain\Shared\Allergen;
use App\Domain\Shared\Money;

/**
 * The core BitePlate menu every location inherits. Concrete franchise factories
 * extend this and append or override categories with their local specialities.
 *
 * Note how every item is built through the per-category {@see MenuItemFactory}
 * objects rather than `new Starter(...)` — Factory Method nested inside Abstract
 * Factory keeps creation rules (validation, defaults) in exactly one place.
 */
abstract class AbstractMenuFactory implements MenuFactory
{
    protected StarterFactory $starters;
    protected MainCourseFactory $mains;
    protected DessertFactory $desserts;
    protected BeverageFactory $beverages;

    public function __construct()
    {
        $this->starters = new StarterFactory();
        $this->mains = new MainCourseFactory();
        $this->desserts = new DessertFactory();
        $this->beverages = new BeverageFactory();
    }

    public function starters(): array
    {
        return [
            $this->starters->create(new MenuItemSpec('ST-SOUP', 'Soup of the Day', 'Chef’s daily seasonal soup with sourdough', Money::of(5.50), [Allergen::Gluten])),
            $this->starters->create(new MenuItemSpec('ST-BRUS', 'Bruschetta', 'Vine tomatoes, basil, garlic on toasted ciabatta', Money::of(6.25), [Allergen::Gluten])),
            $this->starters->create(new MenuItemSpec('ST-WING', 'Buffalo Wings', 'Six wings, blue cheese dip', Money::of(7.95), [Allergen::Dairy])),
        ];
    }

    public function mains(): array
    {
        return [
            $this->mains->create(new MenuItemSpec('MN-BURG', 'Classic Cheeseburger', '6oz beef, cheddar, brioche bun, fries', Money::of(13.50), [Allergen::Gluten, Allergen::Dairy])),
            $this->mains->create(new MenuItemSpec('MN-RISO', 'Wild Mushroom Risotto', 'Arborio rice, parmesan, truffle oil', Money::of(14.95), [Allergen::Dairy])),
            $this->mains->create(new MenuItemSpec('MN-STEAK', 'Sirloin Steak', '8oz sirloin, peppercorn sauce, chips', Money::of(22.00), [Allergen::Dairy])),
        ];
    }

    public function desserts(): array
    {
        return [
            $this->desserts->create(new MenuItemSpec('DS-CHEE', 'Baked Cheesecake', 'Vanilla cheesecake, berry compote', Money::of(6.95), [Allergen::Dairy, Allergen::Gluten, Allergen::Egg])),
            $this->desserts->create(new MenuItemSpec('DS-BROWN', 'Chocolate Brownie', 'Warm brownie, salted caramel ice cream', Money::of(7.25), [Allergen::Dairy, Allergen::Gluten, Allergen::Egg, Allergen::Nuts])),
        ];
    }

    public function beverages(): array
    {
        return [
            $this->beverages->create(new MenuItemSpec('BV-COLA', 'Cola', '330ml', Money::of(2.95))),
            $this->beverages->create(new MenuItemSpec('BV-LEMO', 'Fresh Lemonade', 'House-made, mint', Money::of(3.50))),
            $this->beverages->create(new MenuItemSpec('BV-HOUS', 'House Red (175ml)', 'Tempranillo', Money::of(6.50))),
        ];
    }

    public function fullMenu(): array
    {
        return [...$this->starters(), ...$this->mains(), ...$this->desserts(), ...$this->beverages()];
    }
}
