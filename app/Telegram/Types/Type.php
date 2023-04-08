<?php

namespace App\Telegram\Types;

use Illuminate\Support\Collection;

class Type
{

    public FieldList $fields;

    /** @var Collection<int, Type> */
    public Collection $children;

    public ?Type $parent = null;

    public function __construct(
        public readonly string $name,
        public readonly string $description,
    ) {
        $this->fields = new FieldList();
        $this->children = new Collection();
    }

    public function importFields(array $fields)
    {
        foreach ($fields as $field) {
            $this->fields->add(
                new Field(
                    $field['field'],
                    $field['type'],
                    $field['description'],
                )
            );
        }

        $this->fields = $this->fields->sortBy(fn(Field $field) => $field->optional());
    }

    public function isParent(): bool
    {
        return $this->children->count() > 0;
    }

    public function isChild(): bool
    {
        return $this->parent !== null;
    }

    public function namespace(): string
    {
        return config('tellaptepab.namespace');
    }

    public function className(): string
    {
        return $this->namespace() . '\\' . $this->name;
    }

    public function parentClassName(): string
    {
        return $this->parent?->className() ?? config('tellaptepab.parent_class');
    }

    public function childIdentifier(): ?string
    {
        if ($this->children->count() === 0) {
            return null;
        }

        return $this->children->first()->fields
            ->first(fn(Field $field) => $field->value() !== null)
            ?->name;
    }

    /**
     * @return \Generator<string, void, void, Type>
     */
    public function childMap(): \Generator
    {
        if ($this->children->count() === 0) {
            return [];
        }

        $key = $this->childIdentifier();
        foreach ($this->children as $child) {
            $value = $child->fields->firstWhere('name', $key)?->value();
            yield $value => $child;
        }
    }


}
