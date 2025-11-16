<?php
// ui/Controllers/PackagesController.php

namespace SFPP\UI\Controllers;

/**
 * Handles front-end routes related to Website Packages
 * (list, edit, etc.).
 *
 * For MVP, this will:
 * - fetch WebsitePackage models from a repository
 * - pass them to views in /ui/Views/
 */
class PackagesController
{
    public function list()
    {
        // Later:
        // 1. Load WebsitePackage models from repository.
        // 2. Include /ui/Views/packages-list.php with data.
    }

    public function edit( $id = null )
    {
        // Later:
        // - If $id is null, show "create" form.
        // - Else load WebsitePackage $id and show "edit" form.
    }
}
