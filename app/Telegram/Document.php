<?php

namespace App\Telegram;

use App\Telegram\Methods\Method;
use App\Telegram\Types\Field;
use App\Telegram\Types\Type;
use Illuminate\Support\Collection;

class Document
{

    /** @var Collection<string, Type> */
    public readonly Collection $types;

    /** @var Collection<string, Method> */
    public readonly Collection $methods;

    public function __construct()
    {
        $this->types = new Collection();
        $this->methods = new Collection();
    }

    public function findParentType(string $childClass): ?Type
    {
        foreach ($this->types as $type) {
            if ($type->children->contains($childClass)) {
                return $type;
            }
        }
        return null;
    }

    public function pullUpCommonFields()
    {
        /** @var Collection<int, Type> $parentTypes */
        $parentTypes = $this->types->filter->isParent();
        foreach ($parentTypes as $parent) {

            // Collect common field names
            $commonFieldNames = null;
            foreach ($parent->children as $child) {
                $commonFieldNames ??= $child->fields->pluck('name');
                $commonFieldNames = $commonFieldNames->intersect($child->fields->pluck('name'));
            }

            // Add/Clone common fields to parent
            foreach ($parent->children->first()->fields as $childField) {
                if (! $commonFieldNames->contains($childField->name)) {
                    continue;
                }

                $parent->fields->add(
                    new Field(
                        $childField->name,
                        $childField->type,
                        preg_replace('/, (must be|always) .+$/u', '', $childField->description),
                    )
                );
            }

            // Don't create properties for common fields in children
            foreach ($parent->children as $child) {
                $child->fields->each(function (Field $field) use ($commonFieldNames) {
                    if ($commonFieldNames->contains($field->name) && $field->value() === null) {
                        $field->property = false;
                    }
                });
            }

        }
    }

}
