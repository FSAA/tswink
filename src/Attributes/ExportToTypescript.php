<?php

declare(strict_types=1);

namespace TsWink\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class ExportToTypescript
{
    public ?string $name;

    /**
     * Will mark the method to be exported to TypeScript via tswink.
     * @param string|null $name The name to use in TypeScript. If null, the method name will be used.
     */
    public function __construct(
        ?string $name = null,
    ) {
        $this->name = $name;
    }
}
