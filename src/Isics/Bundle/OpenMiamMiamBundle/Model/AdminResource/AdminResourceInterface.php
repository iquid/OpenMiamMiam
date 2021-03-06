<?php

/*
 * This file is part of the OpenMiamMiam project.
 *
 * (c) Isics <contact@isics.fr>
 *
 * This source file is subject to the AGPL v3 license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Isics\Bundle\OpenMiamMiamBundle\Model\AdminResource;

/**
 * Interface AdminResourceInterface
 */
interface AdminResourceInterface
{
    /**
     * Returns type of resource
     *
     * @return string
     */
    public function getType();
}
