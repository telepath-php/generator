<?php

namespace App\Telegram\Types;

class Type
{

    public readonly FieldList $fields;

    public ?Type $parent = null;

    public function __construct(
        public readonly string $name,
        public readonly string $description,
    ) {
        $this->fields = new FieldList();
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
    }

}
