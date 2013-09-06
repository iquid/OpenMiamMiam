<?php

/*
 * This file is part of the OpenMiamMiam project.
 *
 * (c) Isics <contact@isics.fr>
 *
 * This source file is subject to the AGPL v3 license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Isics\Bundle\OpenMiamMiamBundle\Model\Admin;

/**
 * Class AdminResourceInterface
 */
interface AdminResourceInterface
{
    /**
     * Returns the name
     *
     * @return string
     */
    public function getName();

    /**
     * Returns the type
     *
     * @return string
     */
    public function getType();

    /**
     * Returns the route
     *
     * @return string
     */
    public function getRoute();
}