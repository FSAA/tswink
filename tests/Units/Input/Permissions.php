<?php

declare(strict_types=1);

namespace TsWinkTests\Units\Input;

use TsWink\Attributes\ExportToTypescript;

enum Permissions: int
{
    case ADMIN = 1;
    case EDITOR = 2;
    case VIEWER = 3;
    case CONTRIBUTOR = 4;

    /**
     * @return array<int, Permissions>
     */
    #[ExportToTypescript]
    public static function creationPermissions(): array
    {
        return [self::ADMIN, self::EDITOR];
    }

    /**
     * @return array<int, Permissions>
     */
    #[ExportToTypescript("managePermissions")]
    public static function managementPermissions(): array
    {
        return [self::ADMIN];
    }
}
