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

        $this->fields = $this->fields->sortBy(fn (Field $field) => $field->optional());
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
        return config('generator.type.namespace');
    }

    public function className(): string
    {
        return $this->namespace().'\\'.$this->name;
    }

    public function parentClassName(): string
    {
        return $this->parent?->className() ?? config('generator.type.parent_class');
    }

    protected ?string $childIdentifierCache;

    public function childIdentifier(): ?string
    {
        if ($this->children->count() === 0) {
            return null;
        }

        if (! isset($this->childIdentifierCache)) {

            // We need to look in every child since "InaccessibleMessage" contains the hint but "Message" does not.
            $children = [];
            foreach ($this->children as $child) {
                foreach ($child->fields as $field) {
                    if (($value = $field->valueFromDescription()) !== null) {
                        $children[$child->name][$field->name] = $value;
                    }
                }
            }

            if (count($children) === 0) {
                return null;
            }

            // Debug
            //            ray($children)->label($this->name);

            $keys = array_keys(collect($children)->first());
            $childIdentifier = $keys[0];

            $this->childIdentifierCache = $childIdentifier;

        }

        return $this->childIdentifierCache;
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
        $valuePairs = 0;
        $default = null;

        foreach ($this->children as $child) {
            $value = $child->fields->firstWhere('name', $key)?->value();

            if ($value === null) {
                $default = $child;

                continue;
            }

            $valuePairs++;
            yield $value => $child;
        }

        if ($default && $valuePairs > 0) {
            yield null => $default;
        }
    }
}
