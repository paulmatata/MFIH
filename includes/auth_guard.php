<?php

/**
 * FIH Farmer Authentication Guard
 *
 * Protects farmer-only pages using the existing
 * authentication service in auth.php.
 */

require_once __DIR__ . '/auth.php';


/**
 * Require the current user to be an authenticated farmer.
 */
function require_farmer(): void
{
    require_role('farmer');
}


/**
 * Return the authenticated farmer's user ID.
 *
 * Returns null when no authenticated user exists.
 */
function authenticated_farmer_id(): ?string
{
    if (!is_authenticated()) {
        return null;
    }

    if (authenticated_user_role() !== 'farmer') {
        return null;
    }

    return authenticated_user_id();
}
?>