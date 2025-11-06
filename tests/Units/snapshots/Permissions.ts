export enum Permissions {
    ADMIN = 1,
    EDITOR = 2,
    VIEWER = 3,
    CONTRIBUTOR = 4,
}

export const PermissionsService = {
    creationPermissions: () => {
        return [
            Permissions.ADMIN,
            Permissions.EDITOR,
        ];
    },
    managePermissions: () => {
        return [
            Permissions.ADMIN,
        ];
    },
}

// <non-auto-generated-code>

// </non-auto-generated-code>
