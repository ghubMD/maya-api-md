<?php

namespace App\Story;

use Zenstruck\Foundry\Story;
use App\Factory\CategorieFactory;

final class DefaultCategoriesStory extends Story
{
    public function build(): void
    {
        // TODO build your story here (https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#stories)
        CategorieFactory::createMany(10);
    }
}
